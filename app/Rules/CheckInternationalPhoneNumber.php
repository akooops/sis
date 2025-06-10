<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

class CheckInternationalPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; 
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($value, null);
            
            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                $fail('The :attribute must be a valid international phone number with country code.');
            }

            // Ensure the number is in international format
            if (!str_starts_with($value, '+')) {
                $fail('The :attribute must start with a "+" followed by the country code.');
            }
        } catch (NumberParseException $e) {
            $fail('The :attribute must be a valid international phone number with country code.');
        }
    }
}
