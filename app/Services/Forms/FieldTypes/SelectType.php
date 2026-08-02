<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;
use Illuminate\Validation\Rule;

/** A dropdown. Each option's label is translated; its value never is. */
class SelectType extends BaseFieldType
{
    public function code(): string
    {
        return 'select';
    }

    public function label(): string
    {
        return 'Dropdown';
    }

    public function icon(): string
    {
        return 'ki-arrow-down';
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
            new FieldData(key: 'is_multiple', label: 'Allow several answers', type: 'switch', default: false),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        $values = $this->optionValues($field, $context);

        /*
         * The '.*' half is not optional. Constraining only the array itself
         * would let a multi-select accept any strings at all, and they would be
         * stored as the answer and appear in the CSV export.
         */
        if ($this->setting($field, 'is_multiple')) {
            return [
                '' => [$this->presence($field), 'array'],
                '.*' => ['string', Rule::in($values)],
            ];
        }

        return ['' => [$this->presence($field), 'string', Rule::in($values)]];
    }

    public function store(FormField $field, mixed $value): mixed
    {
        if ($this->setting($field, 'is_multiple')) {
            return is_array($value) ? array_values($value) : [];
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
