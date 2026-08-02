<?php

namespace App\Services\Forms\FieldTypes;

use App\Models\FormField;
use App\Rules\PhoneNumber;
use App\Services\Phone\PhoneFormatter;

/**
 * A phone number, normalised to E164 before it is validated or stored.
 *
 * The house pattern for this is the NormalizesPhones trait on a Data class, but
 * there is no Data class here — the answer keys are dynamic — so this reuses the
 * one piece that matters, PhoneFormatter::e164(). Unparseable input is left
 * as-is so the validator reports it, rather than silently becoming null.
 */
class PhoneType extends BaseFieldType
{
    public function code(): string
    {
        return 'phone';
    }

    public function label(): string
    {
        return 'Phone';
    }

    public function icon(): string
    {
        return 'ki-phone';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        return ['' => [$this->presence($field), 'string', new PhoneNumber]];
    }

    public function store(FormField $field, mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return PhoneFormatter::e164($value) ?? trim($value);
    }
}
