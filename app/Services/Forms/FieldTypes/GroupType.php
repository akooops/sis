<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;
use App\Services\Forms\SubmissionValidator;
use Illuminate\Support\Collection;

/**
 * A repeatable group: a set of child fields the visitor can add more than once.
 *
 * THE ONE ELEMENT THAT RECURSES. Its answer is a list of objects rather than a
 * scalar or a list of scalars:
 *
 *     data['education'] = [{institution: …, degree: …}, …]
 *
 * and that shape is what keeps form_submissions.data flat. The stored map still
 * holds exactly one entry per top-level capturing field — a group just happens to
 * put a list in its slot — so every reader downstream (the drawer, the CSV, the
 * webhook body) keeps addressing answers by field key and nothing has to learn
 * about parentage to read a submission.
 *
 * Rules compile through the SAME keyed-suffix mechanism every other element uses.
 * SubmissionValidator writes `fields.<key><suffix>` for whatever suffixes an
 * element returns, so returning `.*.institution` here produces
 * `fields.education.*.institution` with no special case anywhere in the validator.
 *
 * Children are real form_fields rows (parent_form_field_id), so each one keeps its
 * own registry-declared settings, validation and translations — a child is a
 * normal element that happens to live inside a group, and is validated and stored
 * by its own type exactly as it would be on a page.
 */
class GroupType extends BaseFieldType
{
    /** What a new group allows before the admin touches it. */
    public const DEFAULT_MAX = 10;

    /**
     * The hard ceiling, whatever the admin types.
     *
     * Validation cost is instances × children: at 25 a six-field group is already
     * 150 compiled rules and 150 posted inputs, and no honest form needs more.
     * Clamped rather than rejected so an old row with a larger number keeps
     * working instead of 500ing a live form.
     */
    public const MAX_INSTANCES = 25;

    public function code(): string
    {
        return 'group';
    }

    public function label(): string
    {
        return 'Repeatable group';
    }

    public function icon(): string
    {
        return 'ki-copy';
    }

    public function group(): string
    {
        return 'layout';
    }

    public function hasChildren(): bool
    {
        return true;
    }

    /**
     * Label only. A container has nothing to place-hold and no value of its own;
     * its label is the fieldset legend, and the add/remove wording comes from the
     * `forms.group.*` catalogue keys interpolated with it, so a group needs no
     * per-group button copy translating nine times over.
     *
     * @return array<int, string>
     */
    public function translatable(): array
    {
        return ['label'];
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [
            new FieldData(
                key: 'min_instances',
                label: 'Minimum entries',
                type: 'number',
                default: 0,
                help: 'The visitor cannot remove below this. Marking the group required also forces at least one.',
            ),
            new FieldData(
                key: 'max_instances',
                label: 'Maximum entries',
                type: 'number',
                default: self::DEFAULT_MAX,
                help: 'Up to '.self::MAX_INSTANCES.'.',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        $min = max(0, (int) $this->setting($field, 'min_instances', 0));

        $rules = ['' => [$this->presence($field), 'array', 'max:'.$this->maxInstances($field)]];

        if ($min > 0) {
            $rules[''][] = 'min:'.$min;
        }

        /*
         * Each child compiles under `.*.<child key>`, so a child's own '' and
         * '.*' suffixes survive: a multi-select inside a group lands correctly at
         * fields.education.*.tags.* without the child type knowing it is nested.
         *
         * An empty group posts nothing, and a wildcard over an empty array
         * matches nothing — so a required child inside an optional group is only
         * enforced once the visitor has actually added an entry, which is what
         * makes "add none, or fill one properly" work.
         */
        foreach ($this->children($field) as $child) {
            $element = $child->element();

            if (! $element || ! $element->isInput()) {
                continue;
            }

            foreach ($element->rules($child, $context) as $suffix => $set) {
                $rules[".*.{$child->key}{$suffix}"] = $set;
            }
        }

        return $rules;
    }

    /**
     * Normalise every instance through its children's own store().
     *
     * Unknown keys are dropped rather than trusted — the posted body is visitor
     * input and a group is the one place it arrives shaped like a nested object.
     * Wholly blank instances are dropped too: clicking Add and then leaving the
     * row alone must not store a row of nulls that the CSV and the drawer would
     * then render as a real entry.
     */
    public function store(FormField $field, mixed $value): mixed
    {
        if (! is_array($value)) {
            return [];
        }

        $children = $this->children($field);
        $max = $this->maxInstances($field);
        $out = [];

        foreach ($value as $instance) {
            if (count($out) >= $max) {
                break;
            }

            if (! is_array($instance)) {
                continue;
            }

            $row = [];

            foreach ($children as $child) {
                $element = $child->element();

                if (! $element || ! $element->isInput()) {
                    continue;
                }

                $answer = $instance[$child->key] ?? null;

                $row[$child->key] = SubmissionValidator::blank($answer)
                    ? null
                    : $element->store($child, $answer);
            }

            if (array_filter($row, fn ($v) => $v !== null && $v !== [] && $v !== '') !== []) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * One cell for the whole group, so a CSV keeps a stable column width.
     *
     * Exploding into education_1_institution… would make the column count depend
     * on the widest row in the export, and two exports of the same form would
     * stop lining up.
     */
    public function display(FormField $field, mixed $value, ?string $locale = null): string
    {
        if (! is_array($value) || $value === []) {
            return '';
        }

        $children = $this->children($field);
        $rows = [];

        foreach ($value as $instance) {
            if (! is_array($instance)) {
                continue;
            }

            $parts = [];

            foreach ($children as $child) {
                $element = $child->element();

                if (! $element || ! $element->isInput()) {
                    continue;
                }

                $rendered = $element->display($child, $instance[$child->key] ?? null, $locale);

                if ($rendered !== '') {
                    $parts[] = $rendered;
                }
            }

            if ($parts !== []) {
                $rows[] = implode(' — ', $parts);
            }
        }

        return implode('; ', $rows);
    }

    /** The admin's ceiling, clamped to what the form can afford to compile. */
    public function maxInstances(FormField $field): int
    {
        $max = (int) ($this->setting($field, 'max_instances') ?? self::DEFAULT_MAX);

        return max(1, min($max, self::MAX_INSTANCES));
    }

    public function minInstances(FormField $field): int
    {
        $min = max(0, (int) $this->setting($field, 'min_instances', 0));

        return min($min, $this->maxInstances($field));
    }

    /**
     * Eager-loaded by every caller on the submit path
     * (`topLevelFields.children.options`); the lazy read is the fallback for a
     * group loaded on its own.
     *
     * @return Collection<int, FormField>
     */
    protected function children(FormField $field): Collection
    {
        return $field->children;
    }
}
