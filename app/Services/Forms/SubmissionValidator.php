<?php

namespace App\Services\Forms;

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
        return Validator::make(
            ['fields' => $answers],
            $this->rules($form, $fields, $session),
            [],
            $this->attributes($fields, $locale),
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FormField>  $fields
     * @return array<string, mixed>
     */
    public function rules(Form $form, $fields, ?string $session = null): array
    {
        // One pass for every option on the form, so a 20-choice form is one
        // query rather than twenty.
        $options = [];

        foreach ($fields as $field) {
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
             */
            if ($field->type === 'file' && $session !== null) {
                $extensions = method_exists($element, 'extensions') ? $element->extensions($field) : [];
                $rule = new PublicFormUpload($session, $extensions);

                $rules["fields.{$field->key}.*"][] = $rule;
                $rules["fields.{$field->key}"][] = $rule;
            }

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

            $out[$field->key] = $this->blank($value) ? null : $element->store($field, $value);
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

            $out[$field->key] = [
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
     * Whether the visitor answered at all — null, an empty/whitespace string or
     * an empty array all mean they did not. `0`, `'0'` and `false` are answers
     * and must survive, so this is never `empty()`.
     */
    protected function blank(mixed $value): bool
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
