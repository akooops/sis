<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;

/**
 * A free-text list — skills, interests, keywords — entered as removable pills.
 *
 * NOT a multi-select. A select offers a fixed set the admin wrote down; this
 * accepts whatever the visitor types, which is the point for anything the form
 * author cannot enumerate in advance. So it owns no form_field_options and its
 * '.*' rule bounds length rather than membership.
 *
 * The answer is a plain array of strings, deduplicated case-insensitively — the
 * visitor typing "Arabic" and "arabic" meant one skill, and storing both would
 * put two of them in the export and in every count built on it.
 */
class TagsType extends BaseFieldType
{
    public function code(): string
    {
        return 'tags';
    }

    public function label(): string
    {
        return 'Tag list';
    }

    public function icon(): string
    {
        return 'ki-tag';
    }

    /**
     * @return array<int, FieldData>
     */
    public function validations(): array
    {
        return [
            new FieldData(key: 'min_items', label: 'Minimum tags', type: 'number'),
            new FieldData(key: 'max_items', label: 'Maximum tags', type: 'number', default: 20),
            new FieldData(key: 'max_length', label: 'Maximum length of one tag', type: 'number', default: 64),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        $rules = ['' => [$this->presence($field), 'array']];

        if (($min = $this->option($field, 'min_items')) !== null) {
            $rules[''][] = 'min:'.(int) $min;
        }

        $rules[''][] = 'max:'.(int) ($this->option($field, 'max_items') ?? 20);

        return $rules + ['.*' => ['string', 'max:'.(int) ($this->option($field, 'max_length') ?? 64)]];
    }

    public function store(FormField $field, mixed $value): mixed
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        $seen = [];

        foreach ($value as $tag) {
            if (! is_string($tag)) {
                continue;
            }

            $tag = trim($tag);
            $fold = mb_strtolower($tag);

            if ($tag === '' || isset($seen[$fold])) {
                continue;
            }

            $seen[$fold] = true;
            $out[] = $tag;
        }

        return $out;
    }
}
