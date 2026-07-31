<?php

namespace App\Contracts\Integrations;

use App\Data\Integration\CaptchaResultData;

/**
 * A driver that verifies a captcha token. Resolved through the Captcha service,
 * which binds the active (or explicitly chosen) integration's config.
 *
 * Implementations must not throw: this runs on public forms.
 */
interface VerifiesCaptcha
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function verify(string $token, array $config, ?string $ip = null): CaptchaResultData;
}
