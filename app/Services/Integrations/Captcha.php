<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\VerifiesCaptcha;
use App\Data\Integration\CaptchaResultData;
use App\Models\Integration;

/**
 * Captcha channel. Consistent with Email, Sms and Ai:
 *   Captcha::default()->verify($request->input('captcha_token'), $request->ip());
 *   Captcha::default()->siteKey();
 */
class Captcha
{
    public function __construct(protected ?Integration $integration) {}

    public static function default(): self
    {
        return new self(Integration::activeFor('captcha'));
    }

    public static function for(string $idOrName): self
    {
        return new self(Integration::query()
            ->ofType('captcha')
            ->where(fn ($q) => $q->whereKey($idOrName)->orWhere('name', $idOrName))
            ->firstOrFail());
    }

    /** Whether a form should render a challenge and require its token. */
    public function enabled(): bool
    {
        return $this->driver() !== null;
    }

    /** Public value, safe to render into the page. */
    public function siteKey(): ?string
    {
        return $this->integration?->config['site_key'] ?? null;
    }

    public function verify(?string $token, ?string $ip = null): CaptchaResultData
    {
        $driver = $this->driver();

        // Nothing configured: the feature is off and public forms keep working,
        // the same way Email/Sms degrade rather than break the caller.
        if (! $driver) {
            return CaptchaResultData::passed();
        }

        $token = trim((string) $token);

        if ($token === '') {
            return CaptchaResultData::failed();
        }

        return $driver->verify($token, $this->integration->config ?? [], $ip);
    }

    protected function driver(): ?VerifiesCaptcha
    {
        // A row outlives its driver if the class is dropped from config, and the
        // registry throws on an unknown code — which would 500 a public form.
        if (! $this->integration || ! app(Registry::class)->has($this->integration->driver)) {
            return null;
        }

        $driver = $this->integration->resolveDriver();

        return $driver instanceof VerifiesCaptcha ? $driver : null;
    }
}
