<?php

namespace App\Rules;

use App\Services\Forms\IpValue;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A single IPv4/IPv6 address OR a CIDR range.
 *
 * Laravel's `ip` rule rejects anything with a prefix, and `ip|nullable` plus a
 * regex would accept ranges nothing can parse. This defers to the same
 * inet_pton() parsing the matcher uses, so a value that passes here is a value
 * FormBlockedIp::matches() can act on — a malformed entry is a 422, never a row
 * that silently matches nobody.
 */
class IpOrCidr implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && IpValue::isValid($value)) {
            return;
        }

        // Message resolved from lang/{locale}/validation.php ("ip_or_cidr" key).
        $fail('validation.ip_or_cidr')->translate();
    }
}
