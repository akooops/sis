<?php

namespace App\Services\Forms\FieldTypes;

use App\Contracts\Forms\FieldType;
use App\Data\Integration\FieldData;
use App\Models\FormField;

/**
 * Shared behaviour for every element.
 *
 * Subclasses override only what differs, which is what keeps a new element to
 * one small class.
 */
abstract class BaseFieldType implements FieldType
{
    public function icon(): string
    {
        return 'ki-abstract-26';
    }

    public function group(): string
    {
        return 'input';
    }

    public function isInput(): bool
    {
        return true;
    }

    public function hasOptions(): bool
    {
        return false;
    }

    public function hasChildren(): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    public function translatable(): array
    {
        return ['label', 'placeholder', 'value'];
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [];
    }

    /**
     * @return array<int, FieldData>
     */
    public function validations(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        return ['' => [$this->presence($field), 'string']];
    }

    public function store(FormField $field, mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }

    public function display(FormField $field, mixed $value, ?string $locale = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
    }

    /**
     * `required` or `nullable` — never `sometimes`. A field the visitor skipped
     * arrives as null rather than absent, and `sometimes` would let a required
     * one pass simply by being omitted.
     */
    protected function presence(FormField $field): string
    {
        return $field->is_required ? 'required' : 'nullable';
    }

    /** A validation setting the admin filled in, or null. */
    protected function option(FormField $field, string $key, mixed $default = null): mixed
    {
        $value = data_get($field->validation, $key, $default);

        return $value === '' ? $default : $value;
    }

    /** A behaviour setting the admin filled in, or null. */
    protected function setting(FormField $field, string $key, mixed $default = null): mixed
    {
        $value = data_get($field->settings, $key, $default);

        return $value === '' ? $default : $value;
    }

    /**
     * The values this field's options allow.
     *
     * Prefers a pre-loaded list from $context so building rules for a whole form
     * is one query rather than one per choice field.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    protected function optionValues(FormField $field, array $context = []): array
    {
        if (isset($context['options'][$field->getKey()])) {
            return array_values($context['options'][$field->getKey()]);
        }

        return $field->options()->pluck('value')->all();
    }

    /** The min/max length pair most text-ish elements share. */
    protected function lengthValidations(): array
    {
        return [
            new FieldData(key: 'min_length', label: 'Minimum length', type: 'number'),
            new FieldData(key: 'max_length', label: 'Maximum length', type: 'number', default: 255),
        ];
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<int, mixed>
     */
    protected function withLength(FormField $field, array $rules): array
    {
        if (($min = $this->option($field, 'min_length')) !== null) {
            $rules[] = 'min:'.(int) $min;
        }

        if (($max = $this->option($field, 'max_length')) !== null) {
            $rules[] = 'max:'.(int) $max;
        }

        return $rules;
    }
}
