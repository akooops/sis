<?php

namespace App\Data\Form;

use App\Models\Form;
use App\Models\Language;
use App\Services\Forms\FieldTypeRegistry;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * The whole tree, saved in one atomic write.
 *
 * Array POSITION is the order — for pages, for fields inside a page, and for
 * options inside a field. Nothing sends an explicit `order`, so a drag can never
 * produce two elements claiming the same position.
 *
 * Ids are client-generated ULIDs. Every model here uses HasUlids, so the client
 * supplying the id is legitimate, and it is what stops a save from re-keying
 * every card and tearing down the canvas mid-edit.
 *
 * Settings and validation rules are generated PER FIELD from the registry, using
 * the type each field actually declares — a select's rules are not a number's.
 * That is why this reads the payload rather than emitting one static rule set.
 */
class UpdateBuilderData extends Data
{
    public function __construct(
        /** @var array<int, array<string, mixed>> */
        public array $pages,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $localeMap = 'array:'.implode(',', $codes);
        $registry = app(FieldTypeRegistry::class);
        $types = $registry->codes();

        $rules = [
            'pages' => ['required', 'array', 'min:1'],
            'pages.*.id' => ['required', 'string', 'ulid'],
            'pages.*.name' => ['required', 'string', 'max:255'],
            'pages.*.is_interstitial' => ['sometimes', 'boolean'],
            // Same handles as a field: the page carries an id and a class so the
            // form's own stylesheet can target it. There is no inline CSS here —
            // styling lives in that stylesheet, not in the record.
            'pages.*.css_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z][\w-]*$/'],
            'pages.*.css_class' => ['nullable', 'string', 'max:255'],
            'pages.*.title' => ['sometimes', $localeMap],
            'pages.*.fields' => ['sometimes', 'array'],

            'pages.*.fields.*.id' => ['required', 'string', 'ulid'],
            'pages.*.fields.*.type' => ['required', Rule::in($types)],
            // Machine name: it is the answer key, the export column header and
            // the webhook mapping source, so it must be a safe identifier.
            'pages.*.fields.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'pages.*.fields.*.is_required' => ['sometimes', 'boolean'],
            'pages.*.fields.*.is_unique' => ['sometimes', 'boolean'],
            'pages.*.fields.*.settings' => ['sometimes', 'nullable', 'array'],
            'pages.*.fields.*.validation' => ['sometimes', 'nullable', 'array'],
            'pages.*.fields.*.target_form_page_id' => ['nullable', 'string', 'ulid'],
            'pages.*.fields.*.css_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z][\w-]*$/'],
            'pages.*.fields.*.css_class' => ['nullable', 'string', 'max:255'],
            'pages.*.fields.*.label' => ['sometimes', $localeMap],
            'pages.*.fields.*.placeholder' => ['sometimes', $localeMap],
            'pages.*.fields.*.value' => ['sometimes', $localeMap],
            'pages.*.fields.*.content' => ['sometimes', $localeMap],
            'pages.*.fields.*.options' => ['sometimes', 'array'],

            'pages.*.fields.*.options.*.id' => ['required', 'string', 'ulid'],
            'pages.*.fields.*.options.*.value' => ['required', 'string', 'max:191'],
            'pages.*.fields.*.options.*.is_default' => ['sometimes', 'boolean'],
            'pages.*.fields.*.options.*.label' => ['sometimes', $localeMap],
        ];

        // Per-field settings/validation, from the type each field declares.
        foreach ($context->payload['pages'] ?? [] as $p => $page) {
            foreach ($page['fields'] ?? [] as $f => $field) {
                $type = $field['type'] ?? null;

                if (! is_string($type) || ! $registry->has($type)) {
                    continue;
                }

                $prefix = "pages.{$p}.fields.{$f}";

                foreach ($registry->settingsRules($type, "{$prefix}.settings") as $key => $rule) {
                    // settingsRules keys validation.* on its own; re-prefix it.
                    $rules[str_starts_with($key, 'validation.') ? "{$prefix}.{$key}" : $key] = $rule;
                }

                // A choice element without options renders an empty control the
                // visitor cannot answer, so it would fail at submit instead.
                if ($registry->type($type)->hasOptions()) {
                    $rules["{$prefix}.options"] = ['required', 'array', 'min:1'];
                }

                // A goto button that points nowhere is a dead end.
                if ($type === 'button' && ($field['settings']['action'] ?? null) === 'goto') {
                    $rules["{$prefix}.target_form_page_id"] = ['required', 'string', 'ulid'];
                }
            }
        }

        return $rules;
    }

    /**
     * A seeded form ships with the app: its settings stay editable, but its
     * structure does not. Enforced here rather than in the controller, because
     * hiding the button is not a guard and the permission gate is off in dev.
     *
     * This CANNOT be a rule keyed 'is_system' — the payload is a page tree and
     * never carries that attribute, and Laravel skips non-implicit rules on
     * absent attributes, so the closure would silently never run. withValidator
     * fires on every validation of this class regardless of the payload, and the
     * error has to be added from an after() hook because passes() replaces the
     * message bag before it runs the rules.
     *
     * The key and wording are the contract the builder UI reads.
     */
    public static function withValidator(Validator $validator): void
    {
        $form = request()->route('form');

        if (! $form instanceof Form || ! $form->isLocked()) {
            return;
        }

        $validator->after(function (Validator $validator) use ($form) {
            $validator->errors()->add(
                'is_system',
                "The {$form->name} form ships with the app, so its fields and pages cannot be changed. Its settings can."
            );
        });
    }
}
