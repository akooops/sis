/**
 * Google Analytics 4 shim for the public form.
 *
 * A SHIM, not an integration: it never loads a script, never knows a
 * measurement id and never decides whether tracking is on. The Blade partial
 * (resources/site/views/forms/partials/analytics.blade.php) renders the tag
 * and sets `window.sisfAnalytics`; this file just turns form milestones into
 * gtag events.
 *
 * It NO-OPS COMPLETELY in three cases, all of them normal:
 *   - the form pins no analytics integration, so nothing was rendered;
 *   - gtag never arrives (blocked, offline, consent tooling not satisfied);
 *   - a call throws for any reason at all.
 * Analytics must never be able to break a form somebody is trying to submit,
 * so every path out of here is silent.
 *
 * EVERY NAME HERE IS PREFIXED `sisf_`, EVENTS AND PARAMETERS BOTH, AND THAT IS
 * NOT DECORATION. GA4's Enhanced Measurement collects `form_start` and
 * `form_submit` itself — it is ON by default on every web stream — and reserves
 * `form_id`, `form_name`, `form_destination` and `form_submit_text` as its own
 * parameters. Sending ours under those names does not add to Google's numbers,
 * it corrupts them: our events land in the same report rows as the automatic
 * ones with different parameters attached, and neither figure can be trusted
 * afterwards. Prefixed, ours are unambiguously ours and Google's stay clean.
 *
 * Events: sisf_form_view, sisf_form_start, sisf_form_step, sisf_form_error,
 * sisf_form_submit, sisf_form_complete, sisf_form_abandon.
 */

/**
 * @param {object|null} config `window.sisfAnalytics`, or null when absent.
 */
export function createFormAnalytics(config = null) {
    const form = config?.form ?? {};
    // Sent with every event so a report can group by form without a join.
    // `sisf_form_id`, not `form_id`: see the note above — that one is Google's.
    const base = { sisf_form_id: form.id ?? null, sisf_form_slug: form.slug ?? null };
    const openedAt = Date.now();

    // Milestones, not counters: each of these fires at most once per page.
    let started = false;
    let submitted = false;
    let abandoned = false;
    let lastPageId = null;
    let steps = 0;

    function send(name, params = {}) {
        // The inline stub in the partial defines gtag() and queues into
        // dataLayer before the remote script lands, so "not a function" here
        // means the tag genuinely is not on this page.
        if (!config || typeof window.gtag !== 'function') return;

        try {
            window.gtag('event', name, { ...base, ...params });
        } catch {
            /* Never surface an analytics failure to the visitor. */
        }
    }

    const engagement = () => Math.round((Date.now() - openedAt) / 1000);

    return {
        /** The form was rendered. */
        view() {
            send('sisf_form_view');
        },

        /** First real interaction — focus, blur or a changed answer. */
        start() {
            if (started) return;

            started = true;
            send('sisf_form_start', { engagement_seconds: engagement() });
        },

        /** A page change, forward or back. */
        step({ pageId = null, stack = [], direction = 'forward' } = {}) {
            this.start();

            lastPageId = pageId;
            steps += 1;

            send('sisf_form_step', {
                page_id: pageId,
                // Depth of the navigation stack, so a `goto` interstitial does
                // not read as progress it is not.
                step_depth: stack.length,
                direction,
                engagement_seconds: engagement(),
            });
        },

        /**
         * Server-side validation rejected the last attempt.
         *
         * Counted from the errors rendered into this page, because a failed
         * submit comes back as a fresh load rather than a client-side event.
         */
        error(count = 0) {
            if (!count) return;

            send('sisf_form_error', { error_count: count });
        },

        /** The form posted. Not the same as completing — the server may reject it. */
        submit() {
            if (submitted) return;

            submitted = true;
            send('sisf_form_submit', {
                engagement_seconds: engagement(),
                steps_taken: steps,
            });
        },

        /** The confirmation page rendered: this one actually landed. */
        complete() {
            send('sisf_form_complete');
        },

        /**
         * The visitor left with the form started and unsent.
         *
         * Guarded on `submitted` because a successful submit also unloads the
         * page — without it every completion would be an abandonment too.
         */
        abandon() {
            if (abandoned || submitted || !started) return;

            abandoned = true;
            send('sisf_form_abandon', {
                page_id: lastPageId,
                engagement_seconds: engagement(),
                steps_taken: steps,
            });
        },
    };
}
