<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;

class CheckInternationalPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow empty values - let the 'required' rule handle that
        if (empty($value) || trim($value) === '') {
            return; 
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            // Parse the phone number
            $phoneNumber = $phoneUtil->parse($value, null);
            
            // Check if the number is valid
            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                $fail(__('validation.custom.phone.invalid'));
                return;
            }

            // Check if it's a possible number
            if (!$phoneUtil->isPossibleNumber($phoneNumber)) {
                $fail(__('validation.custom.phone.impossible'));
                return;
            }

            // Ensure the number is in international format (starts with + or already E164)
            $formattedE164 = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
            
            // Accept both formats: "+966501234567" or international input from intl-tel-input
            if (!str_starts_with($value, '+') && $value !== $formattedE164) {
                $fail(__('validation.custom.phone.international_format'));
                return;
            }

        } catch (NumberParseException $e) {
            switch ($e->getErrorType()) {
                case NumberParseException::INVALID_COUNTRY_CODE:
                    $fail(__('validation.custom.phone.invalid_country_code'));
                    break;
                case NumberParseException::NOT_A_NUMBER:
                    $fail(__('validation.custom.phone.not_a_number'));
                    break;
                case NumberParseException::TOO_SHORT_NSN:
                    $fail(__('validation.custom.phone.too_short'));
                    break;
                case NumberParseException::TOO_LONG:
                    $fail(__('validation.custom.phone.too_long'));
                    break;
                default:
                    $fail(__('validation.custom.phone.invalid'));
                    break;
            }
        }
    }
}
