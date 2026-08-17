{{--
    The site's analytics tag, rendered once from the layout's <head>.

    ONE PROPERTY FOR THE WHOLE SITE, from the `integrations.analytics` setting.
    A form could once pin its own, which meant the same visitor's pageviews and
    form events could land in two accounts that no report could ever join into a
    funnel. That option is gone, and with it the guard this file used to carry
    against configuring the same property twice on a form page.

    THE DRIVER RETURNS DATA, NOT MARKUP — this file owns every angle bracket.
    App\Services\Integrations\Analytics::client() hands over an allow-listed
    array (provider, measurement_id, restrict_ad_features, consent_default) and
    nothing else; the integration's config blob stays encrypted and server-side.
    That split is the security property: a driver that returned an HTML snippet
    would put an admin-typed string straight into a `{!! !!}`, which is stored
    XSS.

    So every value below goes through @js() (JS-context escaping), and the one
    value that lands in an HTML attribute goes through {{ }} — and is ALSO
    already constrained to /^G-[A-Z0-9]{4,20}$/ by the driver, which re-checks it
    on the way out of the encrypted config.

    Renders NOTHING when no analytics integration is configured, when it is
    disabled or deleted, or when its id is malformed. A site without analytics
    must not reach out to Google at all.
--}}
{{-- ONE php-directive form per file, never both the inline and the block one:
     Blade pairs the first opening directive with the first @endphp across the
     WHOLE file, so an inline one above a block swallows every directive between
     them. See partials/ui/pagination.blade.php for the full note. --}}
@php
    $tag = $site->analytics()->client();

    $allowAds = ! ($tag['restrict_ad_features'] ?? true);

    // Admin-chosen, denied unless the integration opted in; anything but the
    // exact string 'granted' falls back to denied.
    $analyticsConsent = ($tag['consent_default'] ?? 'denied') === 'granted' ? 'granted' : 'denied';
@endphp

@if (($tag['provider'] ?? null) === 'ga4')
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $tag['measurement_id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { window.dataLayer.push(arguments); }

        /*
         * CONSENT MODE. analytics_storage IS THE ADMIN'S CHOICE; EVERYTHING
         * ELSE IS DENIED, ALWAYS.
         *
         * This must be the FIRST gtag call, before 'js' and 'config': Consent
         * Mode only governs what the tag does after it is set, so a default
         * declared after the config has already run is too late to stop the
         * cookie it wrote.
         *
         * DENIED (the default, and what an EU-facing site must keep) puts GA4
         * in cookieless-ping mode: events still reach the property, but no
         * client_id is persisted, so THERE IS NO IDENTITY ACROSS PAGE LOADS.
         * Pageviews still count; nothing that needs the SAME visitor recognised
         * across two loads survives — a landing page and the page someone
         * reaches from it cannot be joined into a journey.
         *
         * GRANTED restores that and is what an admin whose audience is outside
         * the EU should pick — it is the `consent_default` switch on the
         * analytics integration.
         *
         * (This used to spell the trade out in terms of the form funnel, back
         * when a form emitted its own sisf_form_* events. It does not any more:
         * a form is measured as an ordinary page, and the submission's own
         * telemetry — our endpoint, not GA — is what reports its funnel.)
         *
         * KNOWN GAP: THIS APP HAS NO CONSENT BANNER. Consent Mode expects a
         * banner to call gtag('consent', 'update', {...}) once the visitor
         * chooses; nothing in this codebase ever does, so whatever is set here
         * is what every visitor keeps for the whole visit. Denied-by-default
         * is defensible as a floor, but it is NOT GDPR/ePrivacy compliance —
         * EU traffic still legally requires a real banner with an affirmative
         * opt-in before any analytics is loaded at all, and the gtag.js above
         * is fetched unconditionally. If this site serves the EU, a banner is
         * still required work, not a nicety — and flipping the switch to
         * granted without one is a compliance decision, not a reporting one.
         */
        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: @js($analyticsConsent),
            functionality_storage: 'denied',
            personalization_storage: 'denied',
            // The one exception, and it is the standard one: security_storage
            // covers fraud prevention, not measurement or advertising.
            security_storage: 'granted',
        });

        gtag('js', new Date());
        gtag('config', @js($tag['measurement_id']), {
            /*
             * Both honoured by GA4, unlike the anonymize_ip this replaced —
             * that one is a Universal Analytics field GA4 ignores outright
             * (it truncates IPs unconditionally, with no setting to change).
             */
            allow_google_signals: @js($allowAds),
            allow_ad_personalization_signals: @js($allowAds),
        });
    </script>
@endif
