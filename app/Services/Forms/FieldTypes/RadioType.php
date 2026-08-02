<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;
use Illuminate\Validation\Rule;

/** Exactly one choice from a visible list. */
class RadioType extends BaseFieldType
{
    public function code(): string
    {
        return 'radio';
    }

    public function label(): string
    {
        return 'Radio group';
    }

    public function icon(): string
    {
        return 'ki-check-circle';
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        return ['' => [$this->presence($field), 'string', Rule::in($this->optionValues($field, $context))]];
    }
}
