<?php

namespace App\Rules;

use App\Services\Integrations\Captcha as CaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the provider token (the "g-recaptcha-response" field) against
 * whatever captcha integration is configured. Passes when none is configured —
 * that call is made in App\Services\Integrations\Captcha::verify().
 *
 * Pair it with requiredIf, never a bare 'required': with no integration set up
 * the form renders no challenge and posts no token, and a bare 'required' would
 * reject every submission. Laravel also skips a rule object on an absent value,
 * so requiredIf is what catches a bot that simply omits the field.
 *
 *   'g-recaptcha-response' => [
 *       Rule::requiredIf(CaptchaService::default()->enabled()),
 *       new Captcha(),
 *   ],
 */
class Captcha implements ValidationRule
{
    public function __construct(private ?string $ip = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $result = CaptchaService::default()->verify(
            is_string($value) ? $value : null,
            $this->ip ?? request()->ip(),
        );

        if ($result->ok) {
            return;
        }

        // Messages resolved from lang/{locale}/validation.php.
        $fail($result->unavailable ? 'validation.captcha_unavailable' : 'validation.captcha')->translate();
    }
}
