/**
 * Public entry point — the end-user side.
 *
 * Deliberately NOT app.js: that one boots createInertiaApp and drags in the
 * whole admin. This mounts a single self-contained Svelte island into a
 * server-rendered Blade page, using the SAME FormRenderer the builder's preview
 * uses. One renderer is the whole point — two would drift within a week.
 *
 * The page hands us its data through <script type="application/json"> blocks
 * rather than a JS variable, because the form schema contains admin-authored
 * HTML and a stray `</script>` inside a JS string literal would break out of it.
 * JSON.parse of a text node cannot.
 */
import { mount } from 'svelte';
import FormRenderer from '@/components/forms/FormRenderer.svelte';
import { createFormAnalytics } from '@/lib/forms/ga';
import { collectEnvironment, createTelemetry } from '@/lib/forms/telemetry';

/** Read and parse a JSON payload block, or null when it is absent/invalid. */
export function readPayload(id) {
    const node = document.getElementById(id);

    if (!node) {
        return null;
    }

    try {
        return JSON.parse(node.textContent);
    } catch {
        return null;
    }
}

function boot() {
    /*
     * Set by the analytics partial, and only when the form pins an enabled
     * analytics integration. Undefined is the ordinary case: the shim then
     * no-ops on every call, so nothing below has to branch on it.
     */
    const tracking = window.sisfAnalytics ?? null;
    const analytics = createFormAnalytics(tracking);

    const root = document.querySelector('[data-sisf-root]');

    if (!root) {
        // No form on this page. The confirmation page is the exception worth
        // reporting: it is the only place that knows a submission landed.
        if (tracking?.stage === 'complete') {
            analytics.complete();
        }

        return;
    }

    const schema = readPayload('sisf-schema');
    const config = readPayload('sisf-config') ?? {};

    if (!schema) {
        return;
    }

    /*
     * The measurement layer. Two different things, deliberately kept apart:
     * `analytics` is the admin's own third-party tag and reports MILESTONES to
     * whoever they pinned; `telemetry` is ours and reports COUNTS AND TIMINGS to
     * our own endpoint, which is what fills the analytics columns on the
     * submission row. Neither knows about the other.
     *
     * It is inert without an endpoint and a token, so the builder preview and a
     * page rendered without them cost nothing.
     */
    const environment = collectEnvironment();
    const telemetry = createTelemetry({
        endpoint: config.telemetryAction ?? null,
        token: config.token ?? null,
        capture: config.capture ?? {},
    });

    mount(FormRenderer, {
        target: root,
        props: {
            schema,
            mode: 'live',
            action: config.action ?? null,
            csrf: config.csrf ?? null,
            token: config.token ?? null,
            honeypot: config.honeypot ?? null,
            uploadAction: config.uploadAction ?? null,
            // Null unless this form pins an enabled captcha integration, in
            // which case the page has already loaded the provider script.
            captcha: config.captcha ?? null,
            // Repopulate after a validation failure, so a rejected submission
            // does not make the visitor retype everything.
            values: { ...(config.old ?? {}) },
            // Posted as hidden client[…] inputs, so the submit reads the same
            // screen, timezone and language the beacon reported.
            client: Object.fromEntries(
                Object.entries(environment).filter(([, value]) => value !== null && value !== undefined),
            ),
            // No onsubmit: the form posts natively, which is what keeps
            // Laravel's withErrors round-trip working.
            onsubmit: null,
            // Two consumers of one callback. analytics.start() is once-guarded,
            // so the third-party tag still costs one event no matter how much
            // the visitor types; telemetry keeps counting.
            oninteract: (event) => {
                analytics.start();
                telemetry.interact(event);
            },
            onstep: (event) => {
                analytics.step(event);
                telemetry.step(event);
            },
        },
    });

    // After mount: the listeners are delegated off the root, and there is
    // nothing under it to delegate from until the renderer has drawn.
    telemetry.attach(root);

    analytics.view();

    // The count the server rejected on the previous attempt: a failed submit
    // returns as a fresh page load, so there is no client event to hang this on.
    analytics.error(tracking?.errors ?? 0);

    /*
     * The renderer owns the <form> and posts it natively, so the submit is
     * observed here rather than through a prop — a capture-phase listener on
     * the mount point sees it whichever button triggered it, and cannot
     * interfere with the post.
     */
    root.addEventListener('submit', () => analytics.submit(), true);

    /*
     * pagehide is the ONLY leave signal, and it is the only one that means the
     * page is actually going away: a close, a navigation, or entry into the
     * back/forward cache.
     *
     * visibilitychange USED to fire this too, and it was wrong. On mobile every
     * app switch, screen lock, notification pull-down and tab change hides the
     * page while the visitor fully intends to come back — and a sent event
     * cannot be taken back, so every one of those was a permanent false
     * abandonment in the property.
     *
     * The trade is deliberate and one-directional: a backgrounded mobile tab the
     * OS later kills fires nothing at all, so that abandonment goes unreported.
     * An undercount is a number you can reason about; an overcount that scales
     * with how distracted the visitor's phone is, is not.
     *
     * abandon() is once-guarded and ignores a submitted form.
     */
    window.addEventListener('pagehide', () => analytics.abandon());
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
