<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

/**
 * Validates that a value is a real phone number written in international
 * format (e.g. +213555123456), using Google's libphonenumber — not a regex.
 * The number must carry its own country code (leading "+").
 */
class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && str_starts_with(trim($value), '+')) {
            try {
                $util = PhoneNumberUtil::getInstance();

                // region = null forces the number to carry its own country code.
                if ($util->isValidNumber($util->parse($value, null))) {
                    return;
                }
            } catch (NumberParseException) {
                // fall through to failure
            }
        }

        // Message resolved from lang/{locale}/validation.php ("phone" key).
        $fail('validation.phone')->translate();
    }
}
