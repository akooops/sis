<?php

namespace App\Services\Integrations\Drivers;

use App\Contracts\Integrations\Driver;
use App\Contracts\Integrations\ProvidesAnalytics;
use App\Data\Integration\FieldData;

/**
 * Google Analytics 4 — the browser tag only.
 *
 * The whole driver is one measurement id and a flag: GA4 is a client-side tag,
 * so the reporting is done by gtag.js in the visitor's browser. There is no
 * secret here at all, which is the point — nothing this returns is confidential,
 * every value is rendered into a public page, and the Measurement Protocol
 * (server-to-server events, which DOES need an api_secret) is deliberately out
 * of scope.
 *
 * Nothing throws: this is resolved while rendering a public form.
 */
class GoogleAnalyticsDriver implements Driver, ProvidesAnalytics
{
    /**
     * A GA4 measurement id: `G-` plus the uppercase alphanumeric stream code.
     *
     * Applied TWICE on purpose — as the schema's `pattern` when the admin saves,
     * and again in client() before the value leaves for the page. The second
     * pass is what makes the id safe to interpolate: a row written before this
     * rule existed, or edited straight in the database, never reaches the view.
     */
    public const MEASUREMENT_ID_PATTERN = '/^G-[A-Z0-9]{4,20}$/';

    public function code(): string
    {
        return 'google_analytics';
    }

    public function type(): string
    {
        return 'analytics';
    }

    public function label(): string
    {
        return 'Google Analytics 4';
    }

    public function icon(): string
    {
        return 'ki-graph-up';
    }

    /**
     * @return array<int, FieldData>
     */
    public function schema(): array
    {
        return [
            // Not secret: it is rendered into the page HTML, exactly like a
            // captcha site key.
            new FieldData(
                key: 'measurement_id',
                label: 'Measurement ID',
                type: 'text',
                required: true,
                help: 'From the GA4 data stream, e.g. G-XXXXXXXXXX.',
                pattern: self::MEASUREMENT_ID_PATTERN,
            ),
            /*
             * NOT anonymize_ip. That switch used to live here and it was a lie
             * on the screen: `anonymize_ip` is a Universal Analytics field and
             * GA4 drops it on the floor — IP anonymisation is unconditional and
             * not configurable there, so the toggle changed precisely nothing
             * whichever way the admin set it.
             *
             * This one GA4 genuinely honours: it turns off Google Signals and ad
             * personalisation for the stream, which is what a privacy review is
             * actually asking about.
             */
            new FieldData(
                key: 'restrict_ad_features',
                label: 'Disable Google Signals and ad personalisation',
                type: 'switch',
                default: true,
                help: 'Sends allow_google_signals and allow_ad_personalization_signals as false, so visits are not used for advertising audiences or remarketing. GA4 truncates IP addresses on its own, always — there is no setting for that.',
            ),
            /*
             * The measurement identity, and the switch that decides whether the
             * funnel exists. On (the default) sends Consent Mode
             * analytics_storage: 'denied', which is cookieless-ping mode: the
             * events arrive but nothing joins them across page loads, and the
             * form page and the confirmation page ARE two page loads. Off
             * restores the normal client_id cookie.
             *
             * There is no consent banner in this app, so whatever is set here
             * is what every visitor gets for the whole visit — turning it off
             * for EU traffic is a compliance decision, not a reporting one.
             */
            new FieldData(
                key: 'consent_default',
                label: 'Deny analytics storage until a consent banner grants it',
                type: 'switch',
                default: true,
                help: 'On, no analytics cookie is ever written, so a visitor has no identity across page loads: the form view and the confirmation cannot be joined and there is NO view-to-completion funnel — only per-page event counts. Turn it off to get normal GA4 measurement, but only where you are not required to ask for consent first: this app ships no consent banner, so nothing will ever grant it later.',
            ),
        ];
    }

    /**
     * The tag parameters, or [] when this integration cannot draw a tag.
     *
     * An ALLOW-LIST, not a config dump: only these four keys ever leave the
     * server, so adding a credential to the schema later cannot leak it into
     * the page by accident.
     */
    public function client(array $config): array
    {
        $id = trim((string) ($config['measurement_id'] ?? ''));

        if (! preg_match(self::MEASUREMENT_ID_PATTERN, $id)) {
            return [];
        }

        return [
            'provider' => 'ga4',
            'measurement_id' => $id,
            // Default on: the safer setting is the one a half-filled row gets —
            // including a row written before this key existed, which is why the
            // fallback is true rather than the raw value.
            'restrict_ad_features' => (bool) ($config['restrict_ad_features'] ?? true),
            // Resolved to the literal Consent Mode word here rather than in the
            // view, so the page never has to know which way the boolean points.
            // Same reason the fallback is true: a row saved before this key
            // existed keeps the privacy-first behaviour it was saved under.
            'consent_default' => ($config['consent_default'] ?? true) ? 'denied' : 'granted',
        ];
    }
}
