<?php

namespace App\Traits\Phone;

use App\Services\Phone\PhoneFormatter;

trait NormalizesPhones
{
    /**
     * The fields to normalize. Defaults to the usual single column.
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
            // Only touch what was actually sent: writing the key back would turn
            // "not provided" into "set it to null" on a partial update.
            if (! array_key_exists($field, $properties)) {
                continue;
            }

            $value = $properties[$field];

            if (! is_string($value)) {
                continue;
            }

            // Fall back to what was typed: an unparseable number has to survive
            // to the validator to be reported, not vanish into null.
            $properties[$field] = PhoneFormatter::e164($value) ?? $value;
        }

        return $properties;
    }
}
