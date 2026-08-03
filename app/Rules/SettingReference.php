<?php

namespace App\Rules;

use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The id a model-typed setting stores must name a record that setting is allowed
 * to point at — not merely a row that exists in the right table.
 *
 * Existence alone is the wrong question. A slot declared as "an AI integration"
 * would otherwise accept the id of an email one: never offered by the picker, but
 * reachable by hand, and the code reading that setting would hand it to the wrong
 * driver. Setting::referenceQuery() answers the narrowed question, and the form
 * sends the same `filter` to the same index endpoint — so what the admin is
 * offered and what the server accepts are one query, not two that agree today.
 *
 * Fails CLOSED: a null query (an alias the registry cannot resolve, or a filter
 * key it cannot apply) rejects every id rather than degrading to "any id will do".
 */
class SettingReference implements ValidationRule
{
    public function __construct(protected Setting $setting) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = $this->setting->referenceQuery();

        // A fresh builder per call, so validating each member of a multi-valued
        // setting does not accumulate whereKey() from the one before it.
        if ($query === null || ! is_string($value) || ! $query->whereKey($value)->exists()) {
            $fail('The selected :attribute is invalid.');
        }
    }
}
