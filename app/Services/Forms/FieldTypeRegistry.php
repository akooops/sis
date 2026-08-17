<?php

namespace App\Services\Forms;

use App\Contracts\Forms\FieldType;
use App\Data\Integration\FieldData;
use Illuminate\Contracts\Container\Container;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Maps an element `code` to its class from config('forms.field_types') and
 * resolves it — the one place an element is looked up, so a new element is a
 * class plus a config line.
 *
 * Modelled on App\Services\Integrations\Registry, down to the settings-rule
 * helper, because the two solve the same problem: a server-declared schema the
 * frontend renders generically.
 */
class FieldTypeRegistry
{
    /** @var array<string, FieldType>|null resolved instances, by code */
    protected ?array $types = null;

    public function __construct(protected Container $app) {}

    /** @return array<string, FieldType> */
    public function all(): array
    {
        if ($this->types !== null) {
            return $this->types;
        }

        $this->types = [];

        foreach (config('forms.field_types', []) as $class) {
            /** @var FieldType $type */
            $type = $this->app->make($class);
            $this->types[$type->code()] = $type;
        }

        return $this->types;
    }

    public function has(string $code): bool
    {
        return array_key_exists($code, $this->all());
    }

    public function type(string $code): FieldType
    {
        return $this->all()[$code] ?? throw new InvalidArgumentException("Unknown form field type [{$code}].");
    }

    /** @return array<int, string> */
    public function codes(): array
    {
        return array_keys($this->all());
    }

    /**
     * Codes that capture an answer. FormField::scopeCapturing() filters on this,
     * so anything that stores a value must report isInput() === true.
     *
     * @return array<int, string>
     */
    public function inputCodes(): array
    {
        return array_keys(array_filter($this->all(), fn (FieldType $type) => $type->isInput()));
    }

    /**
     * Codes allowed INSIDE a repeatable group.
     *
     * Two exclusions, both structural rather than stylistic. A group cannot hold
     * a group: nesting would make the answer a list of lists of objects, which
     * neither the flat `data` map nor the '' / '.*' rule contract can express.
     * And it cannot hold an action: Next, Back and Submit move through the FORM,
     * so a copy of one per repeated entry is three buttons that all do the same
     * thing and one the visitor can press from inside a row they are still
     * filling in.
     *
     * @return array<int, string>
     */
    public function childCodes(): array
    {
        return array_keys(array_filter(
            $this->all(),
            fn (FieldType $type) => ! $type->hasChildren() && $type->group() !== 'action',
        ));
    }

    /**
     * The builder palette: everything an admin can drop onto a canvas, with the
     * schema its inspector renders.
     *
     * @return array<int, array<string, mixed>>
     */
    public function palette(): array
    {
        return array_values(array_map(fn (FieldType $type) => [
            'code' => $type->code(),
            'label' => $type->label(),
            'icon' => $type->icon(),
            'group' => $type->group(),
            'is_input' => $type->isInput(),
            'has_options' => $type->hasOptions(),
            'translatable' => $type->translatable(),
            'settings' => array_map(fn (FieldData $f) => $f->toArray(), $this->settingsFor($type)),
            'validations' => array_map(fn (FieldData $f) => $f->toArray(), $type->validations()),
        ], $this->all()));
    }

    /**
     * Percentages a field may occupy on a wide screen.
     *
     * A LIST, NOT A FREE NUMBER. 37% is meaningless in a wrapping row — it would
     * leave a ragged gap and read as a bug — so the admin picks from widths that
     * actually tile.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public const WIDTHS = [
        ['value' => '100', 'label' => '100%'],
        ['value' => '75', 'label' => '75%'],
        ['value' => '66', 'label' => '66%'],
        ['value' => '50', 'label' => '50%'],
        ['value' => '33', 'label' => '33%'],
        ['value' => '25', 'label' => '25%'],
    ];

    /**
     * An element's own settings, plus the ones EVERY element has.
     *
     * Width is injected here rather than added to BaseFieldType::settings(),
     * because almost every type overrides that method and returns its own array —
     * a base-class default would be silently dropped by most of them, and a new
     * element could forget it entirely. Here there is one place, and no type can
     * opt out or forget.
     *
     * @return array<int, FieldData>
     */
    public function settingsFor(FieldType $type): array
    {
        return [...$type->settings(), new FieldData(
            key: 'width',
            label: 'Width on large screens',
            type: 'select',
            default: '100',
            options: self::WIDTHS,
            help: 'Narrower fields sit side by side. They always fill the width on phones, and neighbours pair up only when they are next to each other.',
        )];
    }

    /**
     * Rules for an element's own settings/validation blobs, keyed by the shape
     * the builder posts.
     *
     * @return array<string, array<int, mixed>>
     */
    public function settingsRules(string $code, string $prefix = 'settings'): array
    {
        if (! $this->has($code)) {
            return [];
        }

        $type = $this->type($code);

        $rules = [];

        // settingsFor(), not settings(): the injected width must be validated
        // like any other setting, or a hand-rolled payload could store anything.
        foreach ($this->settingsFor($type) as $field) {
            foreach ($this->fieldRules($field, "{$prefix}.{$field->key}") as $key => $rule) {
                $rules[$key] = $rule;
            }
        }

        foreach ($type->validations() as $field) {
            foreach ($this->fieldRules($field, "validation.{$field->key}") as $key => $rule) {
                $rules[$key] = $rule;
            }
        }

        return $rules;
    }

    /**
     * The rules for ONE declared field, keyed by the path it is posted at.
     *
     * A map rather than a plain list because a `multiselect` needs two entries —
     * the array itself and its elements.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function fieldRules(FieldData $field, string $key): array
    {
        // A multiselect stores an ARRAY of option values, so the membership rule
        // belongs on the ELEMENTS: Rule::in on the array itself compares the
        // whole array against one option and refuses every non-empty answer.
        // Without the `.*` rule the array would be accepted with anything in it,
        // which for FileType would mean an extension the uploader never allows.
        if ($field->type === 'multiselect') {
            return [
                $key => ['sometimes', 'nullable', 'array'],
                "{$key}.*" => ['string', $this->optionRule($field)],
            ];
        }

        $rule = match ($field->type) {
            'number' => 'numeric',
            'email' => 'email',
            'url' => 'url',
            'switch' => 'boolean',
            'tags' => 'array',
            'select' => $this->optionRule($field),
            default => 'string',
        };

        return [$key => ['sometimes', 'nullable', $rule]];
    }

    /** Membership of the values the field declared. */
    protected function optionRule(FieldData $field): mixed
    {
        return Rule::in(array_map(fn ($o) => $o['value'], $field->options ?? []));
    }
}
