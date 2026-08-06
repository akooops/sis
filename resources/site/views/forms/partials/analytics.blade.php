{{--
    The analytics tag for a public form page.

    THE DRIVER RETURNS DATA, NOT MARKUP — this file owns every angle bracket.
    App\Services\Integrations\Analytics::client() hands over an allow-listed
    array (provider, measurement_id, restrict_ad_features, consent_default) and
    nothing else; the integration's config blob stays encrypted and server-side.
    That split is the
    security property: a driver that returned an HTML snippet would put an
    admin-typed string straight into a `{!! !!}`, which is stored XSS.

    So every value below goes through @js() (JS-context escaping), and the one
    value that lands in an HTML attribute goes through {{ }} — and is ALSO
    already constrained to /^G-[A-Z0-9]{4,20}$/ by the driver, which re-checks
    it on the way out of the encrypted config.

    Renders NOTHING when the form pins no analytics integration, when that
    integration is disabled or deleted, or when its id is malformed. A form
    without analytics must not reach out to Google at all.

    Expects: $analytics (App\Services\Integrations\Analytics), $form, $stage.
--}}
@php($tag = $analytics->client())

@if (($tag['provider'] ?? null) === 'ga4')
    @php($allowAds = ! ($tag['restrict_ad_features'] ?? true))
    {{-- Admin-chosen, denied unless the integration opted in; anything but the
         exact string 'granted' falls back to denied. --}}
    @php($analyticsConsent = ($tag['consent_default'] ?? 'denied') === 'granted' ? 'granted' : 'denied')
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
         * The form page and the confirmation page are two separate loads —
         * sisf_form_view / sisf_form_start / sisf_form_submit land under one
         * throwaway identity and sisf_form_complete under another. That means
         * the view -> complete conversion funnel CANNOT BE JOINED at all: the
         * numbers still add up per property, but no report can tell you which
         * views became completions. That is the entire point of the analytics
         * integration, so this is a large cost, not a rounding error. GA4 can
         * only recover it through behavioural modelling, which needs
         * consent-update signals and traffic volume this site will not have.
         *
         * GRANTED restores the funnel and is what an admin whose audience is
         * outside the EU should pick — it is the `consent_default` switch on
         * the Google Analytics integration.
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

        /*
         * Read by resources/site/js/lib/forms/ga.js. Its presence IS the switch:
         * the shim no-ops when this is undefined (or when gtag never loads,
         * e.g. a blocker), so nothing downstream needs to branch.
         */
        window.sisfAnalytics = @js([
            'provider' => $tag['provider'],
            // 'form' on the form itself, 'complete' on the confirmation page —
            // the shim fires sisf_form_view or sisf_form_complete accordingly.
            'stage' => $stage,
            'form' => [
                'id' => $form->id,
                'slug' => $form->slug,
            ],
            // A failed round-trip comes back as a fresh page load, so the count
            // of server-side errors is how sisf_form_error is detected at all.
            'errors' => count($errors->all()),
        ]);
    </script>
@endif
