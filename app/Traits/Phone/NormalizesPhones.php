<?php

namespace App\Traits\Phone;

use App\Services\Phone\PhoneFormatter;

trait NormalizesPhones
{
    /**
     * Fields to normalize.
     *
     * @return array<int, string>
     */
    protected static function phoneFields(): array
    {
        return ['phone'];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        foreach (static::phoneFields() as $field) {
            // Only touch what was sent: writing the key back would turn absent into null.
            if (! array_key_exists($field, $properties)) {
                continue;
            }

            $value = $properties[$field];

            if (! is_string($value)) {
                continue;
            }

            // Keep what was typed: an unparseable number must reach the validator.
            $properties[$field] = PhoneFormatter::e164($value) ?? $value;
        }

        return $properties;
    }
}
