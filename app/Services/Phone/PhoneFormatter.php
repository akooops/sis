<?php

namespace App\Services\Phone;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Throwable;

/**
 * Phone numbers, in one shape.
 *
 * E164 (+213555123456) is the only form the database ever sees. The same number
 * typed as "+213 555 123 456", "+213-555-123-456" or "00213555123456" is one
 * person, and storing it three ways means `unique` never fires, a search never
 * matches, and two accounts quietly share a phone.
 */
class PhoneFormatter
{
    /**
     * The E164 form of a number, or null if it is not a valid one.
     *
     * Deliberately returns null rather than throwing or echoing the input back:
     * the caller normalises before validation, and handing an unparseable value
     * to the validator unchanged is what lets it reach the database.
     */
    public static function e164(?string $number): ?string
    {
        if ($number === null || trim($number) === '') {
            return null;
        }

        try {
            $util = PhoneNumberUtil::getInstance();

            // region = null: the number must carry its own country code. Guessing
            // a default region would turn a typo into a valid number somewhere.
            $parsed = $util->parse($number, null);

            if (! $util->isValidNumber($parsed)) {
                return null;
            }

            return $util->format($parsed, PhoneNumberFormat::E164);
        } catch (Throwable) {
            return null;
        }
    }
}
