<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\ProvidesAnalytics;
use App\Models\Integration;

/**
 * Analytics channel. Consistent with Email, Sms, Ai and Captcha:
 *   Analytics::default()->client();
 *
 * ONE PROPERTY FOR THE WHOLE SITE, resolved from the `integrations.analytics`
 * setting. There is no forForm() any more: a form could pin its own account,
 * which meant one visitor's pageviews and form events landed in two properties
 * that no report could ever join into a funnel. The site's tag now covers both.
 *
 * Read-only by design: this hands the VIEW the parameters for a browser tag and
 * does nothing else. Server-side reporting (GA4's Measurement Protocol) is not
 * here — the tag in the visitor's browser is what reports.
 */
class Analytics
{
    /**
     * Memoised client payload. null = not resolved yet, [] = resolved and
     * unusable — the two must stay distinguishable or every enabled() call
     * would re-resolve the driver on a form that has no analytics.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $memo = null;

    public function __construct(protected ?Integration $integration) {}

    public static function default(): self
    {
        return new self(Integration::activeFor('analytics'));
    }

    /**
     * Whether the page should load a tag at all.
     *
     * Derived from the payload rather than from the row: a driver returns []
     * when its config cannot produce a working tag, and a form that would emit
     * a broken tag is the same thing as a form with no analytics.
     */
    public function enabled(): bool
    {
        return $this->client() !== [];
    }

    /**
     * The public tag parameters — an allow-list built by the driver.
     *
     * NEVER the integration's config: that blob is encrypted and $hidden so it
     * stays server-side, and this is the one narrow hole punched through it.
     *
     * @return array<string, mixed>
     */
    public function client(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $driver = $this->driver();

        return $this->memo = $driver ? $driver->client($this->integration->config ?? []) : [];
    }

    protected function driver(): ?ProvidesAnalytics
    {
        // A row outlives its driver if the class is dropped from config, and the
        // registry throws on an unknown code — which would 500 a public form.
        if (! $this->integration || ! app(Registry::class)->has($this->integration->driver)) {
            return null;
        }

        $driver = $this->integration->resolveDriver();

        return $driver instanceof ProvidesAnalytics ? $driver : null;
    }
}
