<?php

namespace App\Services\Forms\FieldTypes;

use App\Models\FormField;

/** A block of plain explanatory copy. Display only. */
class ParagraphType extends BaseFieldType
{
    public function code(): string
    {
        return 'paragraph';
    }

    public function label(): string
    {
        return 'Paragraph';
    }

    public function icon(): string
    {
        return 'ki-textalign-left';
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
     * @return array<int, string>
     */
    public function translatable(): array
    {
        return ['content'];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        return [];
    }
}
