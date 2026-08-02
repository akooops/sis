<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;

/** A multi-line text answer. */
class TextareaType extends BaseFieldType
{
    public function code(): string
    {
        return 'textarea';
    }

    public function label(): string
    {
        return 'Long text';
    }

    public function icon(): string
    {
        return 'ki-textalign-justifycenter';
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [
            new FieldData(key: 'rows', label: 'Rows', type: 'number', default: 4),
        ];
    }

    /**
     * @return array<int, FieldData>
     */
    public function validations(): array
    {
        return [
            new FieldData(key: 'min_length', label: 'Minimum length', type: 'number'),
            new FieldData(key: 'max_length', label: 'Maximum length', type: 'number', default: 5000),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        return ['' => $this->withLength($field, [$this->presence($field), 'string'])];
    }
}
