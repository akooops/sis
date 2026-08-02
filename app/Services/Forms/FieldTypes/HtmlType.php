<?php

namespace App\Services\Forms\FieldTypes;

use App\Models\FormField;

/**
 * Rich HTML authored in TinyMCE. Display only.
 *
 * This is the ONLY place TinyMCE appears inside the builder canvas. The rest of
 * the form is structured rows, because per-field validation, per-field analytics
 * and per-option translation all need real records rather than parsed markup.
 *
 * The stored HTML is admin-authored and rendered unescaped on the public page,
 * exactly like Page::content. It is trusted to the same degree and no further —
 * a public visitor can never write into it.
 */
class HtmlType extends BaseFieldType
{
    public function code(): string
    {
        return 'html';
    }

    public function label(): string
    {
        return 'Rich content';
    }

    public function icon(): string
    {
        return 'ki-code';
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
