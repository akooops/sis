<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;

/** A section heading. Display only — it captures nothing. */
class HeadingType extends BaseFieldType
{
    public function code(): string
    {
        return 'heading';
    }

    public function label(): string
    {
        return 'Heading';
    }

    public function icon(): string
    {
        return 'ki-text-bold';
    }

    public function group(): string
    {
        return 'display';
    }

    public function isInput(): bool
    {
        return false;
    }

    /**
     * The visible text is `content`, not `label`: a heading has no control to
     * label, and reusing `label` would put it in the wrong box in the inspector.
     *
     * @return array<int, string>
     */
    public function translatable(): array
    {
        return ['content'];
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [
            new FieldData(key: 'level', label: 'Level', type: 'select', required: true, default: 'h2', options: [
                ['value' => 'h1', 'label' => 'H1'],
                ['value' => 'h2', 'label' => 'H2'],
                ['value' => 'h3', 'label' => 'H3'],
                ['value' => 'h4', 'label' => 'H4'],
                ['value' => 'h5', 'label' => 'H5'],
                ['value' => 'h6', 'label' => 'H6'],
            ]),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        return [];
    }
}
