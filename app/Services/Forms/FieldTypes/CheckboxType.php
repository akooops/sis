<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;
use Illuminate\Validation\Rule;

/** Any number of choices from a visible list. */
class CheckboxType extends BaseFieldType
{
    public function code(): string
    {
        return 'checkbox';
    }

    public function label(): string
    {
        return 'Checkbox group';
    }

    public function icon(): string
    {
        return 'ki-check-squared';
    }

    public function group(): string
    {
        return 'choice';
    }

    public function hasOptions(): bool
    {
        return true;
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [
            new FieldData(key: 'inline', label: 'Lay out in a row', type: 'switch', default: false),
        ];
    }

    /**
     * @return array<int, FieldData>
     */
    public function validations(): array
    {
        return [
            new FieldData(key: 'min_selected', label: 'Minimum choices', type: 'number'),
            new FieldData(key: 'max_selected', label: 'Maximum choices', type: 'number'),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        $rules = [$this->presence($field), 'array'];

        if (($min = $this->option($field, 'min_selected')) !== null) {
            $rules[] = 'min:'.(int) $min;
        }

        if (($max = $this->option($field, 'max_selected')) !== null) {
            $rules[] = 'max:'.(int) $max;
        }

        // Members must be real options — see the note on SelectType.
        return [
            '' => $rules,
            '.*' => ['string', Rule::in($this->optionValues($field, $context))],
        ];
    }

    public function store(FormField $field, mixed $value): mixed
    {
        return is_array($value) ? array_values($value) : [];
    }
}
