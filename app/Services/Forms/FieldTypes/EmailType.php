<?php

namespace App\Services\Forms\FieldTypes;

use App\Models\FormField;

/** An email address. */
class EmailType extends BaseFieldType
{
    public function code(): string
    {
        return 'email';
    }

    public function label(): string
    {
        return 'Email';
    }

    public function icon(): string
    {
        return 'ki-sms';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        return ['' => [$this->presence($field), 'string', 'email:rfc', 'max:255']];
    }

    public function store(FormField $field, mixed $value): mixed
    {
        // Lower-cased so the unique-within-form check cannot be walked past with
        // a different capitalisation of the same address.
        return is_string($value) ? mb_strtolower(trim($value)) : $value;
    }
}
