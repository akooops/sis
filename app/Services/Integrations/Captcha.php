<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\VerifiesCaptcha;
use App\Data\Integration\CaptchaResultData;
use App\Models\Form;
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

    /**
     * The integration a form pinned, or none.
     *
     * find(), deliberately not firstOrFail(): this runs on a public page, and an
     * admin deleting the integration must turn the challenge off, not 500 every
     * visitor. Falls back to nothing rather than to the default account — the
     * page renders one provider's site key, and verifying a token against a
     * different provider's secret fails every legitimate submission.
     */
    public static function forForm(Form $form): self
    {
        if (! $form->is_captcha_enabled || ! $form->captcha_integration_id) {
            return new self(null);
        }

        $integration = Integration::query()
            ->ofType('captcha')
            ->where('is_enabled', true)
            ->find($form->captcha_integration_id);

        return new self($integration);
    }

    /**
     * Whether a form should render a challenge and require its token.
     *
     * BOTH halves of the key pair are part of the condition, and each is here
     * for a different failure:
     *
     *  - No SITE key and the page cannot draw a widget, so the form would demand
     *    a token no visitor could produce — every submission rejected with a
     *    message nobody can satisfy.
     *  - No SECRET and every verify() returns `unavailable`. Since an outage now
     *    fails OPEN (a provider being down must not take every protected form
     *    offline), that combination would render a challenge, dutifully collect
     *    a token, and then wave every submission through — a captcha that looks
     *    present and protects nothing, which is worse than none at all.
     *
     * Same philosophy as forForm()'s find(): a half-configured integration turns
     * the challenge OFF and says so, rather than pretending.
     */
    public function enabled(): bool
    {
        return $this->driver() !== null && filled($this->siteKey()) && filled($this->secretKey());
    }

    /** Public value, safe to render into the page. */
    public function siteKey(): ?string
    {
        return $this->integration?->config['site_key'] ?? null;
    }

    /**
     * Presence check ONLY — never rendered, never returned to a client. It exists
     * so enabled() can tell a usable integration from a half-saved one.
     */
    protected function secretKey(): ?string
    {
        return $this->integration?->config['secret_key'] ?? null;
    }

    /**
     * The driver code the page has to draw a widget for (`recaptcha`).
     *
     * Gated on enabled() rather than reading the column, so a view can hang the
     * provider <script> off this and load NOTHING on the ordinary form that has
     * no captcha — which is almost all of them.
     */
    public function provider(): ?string
    {
        return $this->enabled() ? $this->integration->driver : null;
    }

    /**
     * Provider variant, `v2` or `v3` for reCAPTCHA.
     *
     * Public, like the site key. The client cannot render the right thing
     * without it: v2 draws a checkbox and hands the token to a callback, while
     * v3 draws nothing and the token has to be executed at submit time.
     */
    public function version(): ?string
    {
        return $this->integration?->config['version'] ?? null;
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
