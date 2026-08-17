<?php

namespace App\Services\Forms;

use App\Contracts\Forms\SubmissionRule;
use App\Models\Form;
use App\Models\FormField;
use App\Rules\PublicFormUpload;
use App\Rules\UniqueSubmissionValue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Turns a form's stored field definitions into Laravel rules at submit time.
 *
 * Answers are namespaced under `fields.` so a field keyed `captcha_token` or
 * `submission_token` cannot collide with the envelope around it.
 *
 * :attribute is the field's TRANSLATED label in the visitor's locale, so a
 * French visitor gets Laravel's own French message naming the field they can
 * actually see — rather than a machine key they have never been shown.
 */
class SubmissionValidator
{
    public function __construct(protected FieldTypeRegistry $registry) {}

    /**
     * @param  \Illuminate\Support\Collection<int, FormField>  $fields
     * @param  array<string, mixed>  $answers
     */
    public function make(Form $form, $fields, array $answers, string $locale, ?string $session = null): ValidatorInstance
    {
        // Compacted FIRST, so a form-level rule reading one answer to decide
        // about another sees exactly what will be stored — not a repeat row the
        // visitor abandoned and that storage is about to drop.
        $answers = $this->withoutBlankInstances($fields, $answers);

        return Validator::make(
            ['fields' => $answers],
            $this->withFormRules($form, $answers, $this->rules($form, $fields, $session)),
            [],
            $this->attributes($fields, $locale),
        );
    }

    /**
     * Merge in whatever config('forms.submission_rules') declares for this form.
     *
     * MERGED, NEVER REPLACED: a provider adding a rule to `fields.email` must not
     * wipe the `email:rfc` and `max:255` the element itself declared, which a
     * plain array union would do.
     *
     * A slug is looked up as an array key rather than through config()'s dot
     * path, because a dot in a slug would otherwise read as one more level of
     * nesting and silently find nothing.
     *
     * @param  array<string, mixed>  $answers
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function withFormRules(Form $form, array $answers, array $rules): array
    {
        $providers = config('forms.submission_rules', [])[$form->slug] ?? [];

        foreach ($providers as $class) {
            $provider = app($class);

            // A misconfigured entry must not take a public form down — the same
            // reasoning as FormField::element() returning null for a dropped type.
            if (! $provider instanceof SubmissionRule) {
                continue;
            }

            foreach ($provider->rules($form, $answers) as $path => $set) {
                $rules[$path] = array_merge($rules[$path] ?? [], (array) $set);
            }
        }

        return $rules;
    }

    /**
     * Drop the repeat rows the visitor added and never filled in.
     *
     * VALIDATION AND STORAGE HAVE TO AGREE ABOUT WHAT AN EMPTY ROW IS. A group
     * renders its minimum rows up front and the visitor can add more, so pressing
     * Add and changing their mind leaves a row of empty strings in the post.
     * GroupType::store() already discards those — without this, a required child
     * would reject a submission over a row that was about to be thrown away.
     *
     * KEYS ARE PRESERVED, NOT REINDEXED. The remaining indices are what the error
     * messages are keyed by, and the renderer finds the row to flag by that index
     * — compacting [A, blank, C] to [A, C] would report C's error against index 1
     * and highlight the wrong entry. Storage reindexes instead, because stored
     * data has no DOM to line up with.
     *
     * @param  \Illuminate\Support\Collection<int, FormField>  $fields
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    protected function withoutBlankInstances($fields, array $answers): array
    {
        foreach ($fields as $field) {
            if (! $field->isGroup() || ! is_array($answers[$field->key] ?? null)) {
                continue;
            }

            $answers[$field->key] = array_filter(
                $answers[$field->key],
                fn ($instance) => is_array($instance)
                    && array_filter($instance, fn ($value) => ! static::blank($value)) !== [],
            );
        }

        return $answers;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FormField>  $fields
     * @return array<string, mixed>
     */
    public function rules(Form $form, $fields, ?string $session = null): array
    {
        // One pass for every option on the form, so a 20-choice form is one
        // query rather than twenty. Group children are walked too — a select
        // inside a repeat needs its membership rule as much as one on the page.
        $options = [];

        foreach ($this->withChildren($fields) as $field) {
            if ($field->relationLoaded('options')) {
                $options[$field->getKey()] = $field->options->pluck('value')->all();
            }
        }

        $rules = [];

        foreach ($fields as $field) {
            $element = $field->element();

            // A type dropped from the registry must not 500 a live form; it
            // simply stops being validated, and stops being an input.
            if (! $element || ! $element->isInput()) {
                continue;
            }

            foreach ($element->rules($field, ['options' => $options]) as $suffix => $set) {
                $rules["fields.{$field->key}{$suffix}"] = $set;
            }

            /*
             * A file answer is a media id, and the element's own CleanUpload
             * rule only proves the file is scanned — not that THIS visitor
             * uploaded it. Without the session check anyone could post someone
             * else's media id and attach a stranger's file to their submission.
             *
             * Applied here rather than inside FileType because the session is
             * request state the element has no business knowing, and it must
             * reach file fields nested in a group too — hence uploads(), which
             * returns each one already keyed by its full answer path.
             */
            if ($session !== null) {
                foreach ($this->uploads($field) as $path => $upload) {
                    $uploadElement = $upload->element();

                    $extensions = $uploadElement && method_exists($uploadElement, 'extensions')
                        ? $uploadElement->extensions($upload)
                        : [];

                    $rule = new PublicFormUpload($session, $extensions);

                    $rules["fields.{$path}.*"][] = $rule;
                    $rules["fields.{$path}"][] = $rule;
                }
            }

            // Top-level only. A child cannot be unique — "one answer per form"
            // is meaningless for a value that repeats within a single
            // submission, and the builder refuses to set the flag on one.
            if ($field->is_unique) {
                $rules["fields.{$field->key}"][] = new UniqueSubmissionValue($form, $field);
            }
        }

        return $rules;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FormField>  $fields
     * @return array<string, string>
     */
    public function attributes($fields, string $locale): array
    {
        $attributes = [];

        foreach ($fields as $field) {
            $label = $field->getTranslation('label', $locale, true) ?: $field->key;

            $attributes["fields.{$field->key}"] = $label;
            // Members of an array answer, so "The Colour field" beats
            // "The fields.colour.0 field".
            $attributes["fields.{$field->key}.*"] = $label;

            /*
             * A group's children name themselves, not their group: the visitor
             * looking at the second education row wants "The Institution field is
             * required", not "The Education field is required" (which is also
             * what the group's own rules would say about a different failure) and
             * certainly not "fields.education.1.institution".
             */
            if ($field->isGroup()) {
                foreach ($field->children as $child) {
                    $childLabel = $child->getTranslation('label', $locale, true) ?: $child->key;

                    $attributes["fields.{$field->key}.*.{$child->key}"] = $childLabel;
                    $attributes["fields.{$field->key}.*.{$child->key}.*"] = $childLabel;
                }
            }
        }

        return $attributes;
    }

    /**
     * Normalise answers on their way into storage: phone numbers to E164,
     * numbers to numbers, checkboxes to arrays.
     *
     * THE STORED MAP IS THE WHOLE FORM, IN THE FORM'S OWN ORDER. Every capturing
     * field gets a key — page by page, field by field, see ordered() — and one
     * the visitor left blank gets an explicit null rather than being absent. A
     * map of only what came back cannot be told apart from a map missing the
     * fields that were never asked, and every consumer (the drawer, the CSV, the
     * webhook body) would have to re-read the live form to find out which.
     *
     * "Left blank" is decided HERE rather than in the element, because each type
     * has its own empty value ('' for text, [] for a checkbox group, false for a
     * consent box) and all of them mean the same thing on a submission.
     *
     * @param  \Illuminate\Support\Collection<int, FormField>  $fields
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function normalise($fields, array $answers): array
    {
        $out = [];

        foreach ($this->ordered($fields) as $field) {
            $element = $field->element();

            if (! $element || ! $element->isInput()) {
                continue;
            }

            $value = $answers[$field->key] ?? null;

            $out[$field->key] = static::blank($value) ? null : $element->store($field, $value);
        }

        return $out;
    }

    /**
     * The {key: {label, type}} snapshot stored beside the answers, so a
     * submission stays readable after its form is relabelled or a field removed.
     *
     * Same fields in the same order as normalise(), so the two maps line up key
     * for key and the drawer can walk either one.
     *
     * @param  \Illuminate\Support\Collection<int, FormField>  $fields
     * @return array<string, array{label: string, type: string}>
     */
    public function snapshot($fields, string $locale): array
    {
        $out = [];
        $order = 0;

        foreach ($this->ordered($fields) as $field) {
            if (! $field->element()?->isInput()) {
                continue;
            }

            $entry = [
                'label' => $field->getTranslation('label', $locale, true) ?: $field->key,
                'type' => $field->type,
                /*
                 * THE READING ORDER, AS A NUMBER, BECAUSE KEY ORDER DOES NOT
                 * SURVIVE THE COLUMN.
                 *
                 * This array is built in the form's own order and json_encodes
                 * in that order — and MySQL throws it away. The native JSON type
                 * stores an object in a normalised form with its keys sorted by
                 * length and then lexicographically, so the row reads back as
                 * email, phone, guests, stream, visit_at, full_name… whatever
                 * order it went in as. The admin's submission drawer renders in
                 * snapshot order, so answers came out shuffled with nothing in
                 * the PHP to explain it.
                 *
                 * An integer survives what key order cannot. Kept as a map
                 * rather than switched to a list because every reader looks a
                 * field up by key.
                 */
                'order' => $order++,
            ];

            /*
             * A group carries its children's labels in the same shape, one level
             * down, so the drawer can render a stored instance without going back
             * to the live form — which is the whole point of the snapshot. Without
             * this, relabelling "Institution" to "School" would silently rewrite
             * every historical submission's heading.
             */
            if ($field->isGroup()) {
                $children = [];
                $childOrder = 0;

                foreach ($field->children as $child) {
                    if (! $child->element()?->isInput()) {
                        continue;
                    }

                    $children[$child->key] = [
                        'label' => $child->getTranslation('label', $locale, true) ?: $child->key,
                        'type' => $child->type,
                        'order' => $childOrder++,
                    ];
                }

                $entry['children'] = $children;
            }

            $out[$field->key] = $entry;
        }

        return $out;
    }

    /**
     * The form's reading order: page order, then position within the page.
     *
     * GUARANTEED HERE, not assumed from the caller. Form::fields() orders by
     * `order` alone, and that column is scoped to the PAGE — so on a three-page
     * form it interleaves the pages (every field 0, then every field 1) and the
     * stored answers come out in an order nobody would recognise. The page has
     * to be part of the sort, which is why it is loaded if the caller did not.
     *
     * loadMissing, so the extra query happens once per submit at most and never
     * when the caller already eager-loaded the relation. The trailing `key` is
     * the tie-break that makes the result total rather than merely sorted: two
     * fields sharing a position (or a page whose relation could not be resolved)
     * must not land in whichever order the database happened to return.
     *
     * @param  \Illuminate\Support\Collection<int, FormField>  $fields
     * @return \Illuminate\Support\Collection<int, FormField>
     */
    protected function ordered($fields)
    {
        if (method_exists($fields, 'loadMissing')) {
            $fields->loadMissing('page');
        }

        return $fields->sortBy([
            ['page.order', 'asc'],
            ['order', 'asc'],
            ['key', 'asc'],
        ])->values();
    }

    /**
     * Every field in the set, each group followed by its children.
     *
     * Only for lookups that must cover the whole form — the option pre-load, and
     * nothing else. Rule compilation still walks top-level fields ONLY and lets
     * each group recurse, or a child would be validated twice: once correctly
     * under its group's path, and once as a phantom top-level `fields.<child>`
     * that the visitor never posts and a required child would therefore always
     * fail.
     *
     * @param  \Illuminate\Support\Collection<int, FormField>  $fields
     * @return \Generator<int, FormField>
     */
    protected function withChildren($fields): \Generator
    {
        foreach ($fields as $field) {
            yield $field;

            if ($field->isGroup()) {
                foreach ($field->children as $child) {
                    yield $child;
                }
            }
        }
    }

    /**
     * The file fields reachable from one top-level field, keyed by the ANSWER
     * PATH they occupy rather than by their own key — `cv` on a page, but
     * `education.*.certificate` inside a group.
     *
     * @return array<string, FormField>
     */
    protected function uploads(FormField $field): array
    {
        if ($field->type === 'file') {
            return [$field->key => $field];
        }

        if (! $field->isGroup()) {
            return [];
        }

        $out = [];

        foreach ($field->children as $child) {
            if ($child->type === 'file') {
                $out["{$field->key}.*.{$child->key}"] = $child;
            }
        }

        return $out;
    }

    /**
     * Whether the visitor answered at all — null, an empty/whitespace string or
     * an empty array all mean they did not. `0`, `'0'` and `false` are answers
     * and must survive, so this is never `empty()`.
     *
     * Public and static because GroupType has to make the same judgement about
     * each child inside an instance, and "did they answer?" must mean exactly one
     * thing across the two — a second copy is a second definition waiting to
     * drift.
     */
    public static function blank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return is_array($value) && $value === [];
    }
}
