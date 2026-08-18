/**
 * The public form renderer's boot.
 *
 * This WAS resources/site/js/site.js in its entirety, back when the public site
 * was one feature. It moved here unchanged when the real site landed, so that
 * FormRenderer and its sixteen element components — a static import below — are
 * code-split away from every page that has no form on it.
 *
 * The page hands us its data through <script type="application/json"> blocks
 * rather than a JS variable, because the form schema contains admin-authored
 * HTML and a stray `</script>` inside a JS string literal would break out of it.
 * JSON.parse of a text node cannot.
 */
import { mount, unmount } from 'svelte';
import FormRenderer from '@site/components/forms/FormRenderer.svelte';
import ApplyChoice from '@site/components/forms/ApplyChoice.svelte';
import { collectEnvironment, createTelemetry } from '@site/lib/forms/telemetry';
import { DISCLOSURE_EVENT } from './disclosure';
import { attachPhoneWidget } from './phone';

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

export default function initForms() {
    const root = document.querySelector('[data-sisf-root]');

    // Nothing to mount. Nothing else to do either, now that the form emits no
    // analytics of its own — the page's own tag records the visit like any
    // other page.
    if (!root) {
        return;
    }

    const schema = readPayload('sisf-schema');
    const config = readPayload('sisf-config') ?? {};

    if (!schema) {
        return;
    }

    /*
     * OUR OWN measurement, and the only measurement here. It reports counts and
     * timings to our endpoint, which is what fills the analytics columns on the
     * submission row and drives the form's own analytics screen.
     *
     * There is no third-party tag in this file any more: the site loads one
     * analytics property from the `integrations.analytics` setting, in the
     * layout, and a form is measured by that as an ordinary page.
     *
     * Inert without an endpoint and a token, so a page rendered without them
     * costs nothing.
     */
    const environment = collectEnvironment();
    const telemetry = createTelemetry({
        endpoint: config.telemetryAction ?? null,
        token: config.token ?? null,
        capture: config.capture ?? {},
    });

    /*
     * A form may open with a CHOICE rather than with its first field: fill it in
     * yourself, or upload a CV and have it prefilled. Long forms shown cold are
     * what make people leave.
     *
     * The gate is real — FormRenderer is not mounted until a path is picked —
     * which is what lets the AI path hand it prefilled values, since `values` is
     * an INITIAL prop and cannot be pushed in after mount.
     *
     * A rejected submission skips the gate entirely: the visitor has already
     * chosen once and `old` is carrying their answers, so asking again would
     * throw them away.
     */
    const returning = !!config.returning;

    /**
     * ONE handle to whatever is under `root` — the chooser or the renderer,
     * never both, and never two of either. Every mount goes through clear()
     * first, so a repeated open cannot stack a second renderer on the page and a
     * fast toggle cannot leave an orphan behind the one on screen.
     */
    let mounted = null;

    /** Disconnected on unmount; watchPhoneFields makes a fresh one per mount. */
    let phoneObserver = null;

    /** See the note in showForm() — attach() may run exactly once. */
    let telemetryAttached = false;

    if (config.chooser && !returning) {
        showChooser();
        resetChoiceWhenPutAway();

        return;
    }

    showForm({ ...(config.old ?? {}) });

    function clear() {
        // Before the unmount, or the observer fires on the renderer's own
        // removal and re-scans a subtree already on its way out.
        phoneObserver?.disconnect();
        phoneObserver = null;

        if (mounted) {
            unmount(mounted);
            mounted = null;
        }
    }

    function showChooser() {
        clear();

        mounted = mount(ApplyChoice, {
            target: root,
            props: {
                labels: config.labels ?? {},
                uploadAction: config.uploadAction ?? null,
                parseAction: config.parseAction ?? null,
                token: config.token ?? null,
                csrf: config.csrf ?? null,
                cvKey: config.cvKey ?? 'cv',
                // `old` carries the page's own presets (the hidden job_offer_id),
                // so it has to survive a second trip through the gate.
                onready: ({ values }) => showForm({ ...(config.old ?? {}), ...values }),
            },
        });
    }

    function showForm(values) {
        clear();

        mounted = mount(FormRenderer, {
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
                // Server-rendered copy for the renderer's own chrome, so "Next" and
                // "Step 2 of 3" are not hardcoded English on an Arabic page.
                labels: config.labels ?? null,
                // Repopulate after a validation failure, so a rejected submission
                // does not make the visitor retype everything.
                values,
                // ...and show each rejection in red under the input that caused it,
                // keyed by field key. The server is the only validator — a failed
                // submit returns as a fresh page load, so these arrive in the
                // payload rather than from anything that happened in the browser.
                errors: { ...(config.errors ?? {}) },
                // False on a form's own page, where the page heading and body ARE
                // the form's title, description and content.
                chrome: config.chrome ?? true,
                // Posted as hidden client[…] inputs, so the submit reads the same
                // screen, timezone and language the beacon reported.
                client: Object.fromEntries(
                    Object.entries(environment).filter(([, value]) => value !== null && value !== undefined),
                ),
                // No onsubmit: the form posts natively, which is what keeps
                // Laravel's withErrors round-trip working.
                onsubmit: null,
                oninteract: (event) => telemetry.interact(event),
                onstep: (event) => telemetry.step(event),
            },
        });

        /*
         * ONCE PER PAGE LOAD, NOT ONCE PER MOUNT. attach() is not idempotent: it
         * appends to its own listener list and starts a second heartbeat, so a
         * remount would report every interaction twice and post two beacons.
         *
         * Attaching once is enough because the listeners are delegated off
         * `root` — the Blade shell's own div, which outlives every mount — so
         * they keep working across a reset. Nor is telemetry destroyed there:
         * it is still the same visitor, the same token and the same draft row,
         * and destroy() is terminal.
         */
        if (!telemetryAttached) {
            telemetryAttached = true;
            telemetry.attach(root);
        }

        enhancePhoneFields(root);
        phoneObserver = watchPhoneFields(root);
    }

    /**
     * Backing out of a collapsed panel puts the CHOICE back.
     *
     * The chooser is a gate, not a step: the renderer does not exist until a
     * path is picked. A job posting hides that whole thing behind Apply/Abort,
     * and hiding a panel only hides its subtree — so aborting and pressing Apply
     * again revealed the renderer the visitor had already been given, with no
     * way back to the two options. Somebody who backed out has not chosen, so
     * the next open has to ask again.
     *
     * ONLY ON THE WAY CLOSED. A reset throws away every answer typed so far, so
     * it must never run on an open, and never at all for a returning visitor
     * whose rejected submit is repopulating old() — which is why this is bound
     * inside the gate branch and nowhere else. A form with no chooser (contact,
     * inquiries) binds nothing.
     */
    function resetChoiceWhenPutAway() {
        // On the document, not on `root`: the event bubbles UP from the panel,
        // which is an ancestor of the mount point, so it never passes through
        // it. Containment is the whole test — this needs to know nothing about
        // the panel's id, its markup or which page put a form inside one.
        document.addEventListener(DISCLOSURE_EVENT, (event) => {
            if (event.detail?.open !== false) {
                return;
            }

            const panel = event.target;

            if (panel instanceof Element && panel.contains(root)) {
                showChooser();
            }
        });
    }

    // No leave handler here. There used to be a `pagehide` listener reporting
    // abandonment to the third-party tag; our own telemetry binds `pagehide` and
    // `visibilitychange` itself inside attach(), and it is the only measurement
    // left.
}

/**
 * Give every rendered phone field the country selector.
 *
 * PhoneControl deliberately renders a bare <input type="tel"> and does NOT
 * import intl-tel-input, because it also runs inside the admin builder's
 * preview where ~90 KB of country metadata would be dead weight. On the public
 * site that cost is already paid — site.css imports the plugin's stylesheet and
 * the site's own Blade phone fields use it — so attaching it here is free, and
 * it is what makes the form's phone field match the rest of the site.
 *
 * Enhancement only: with the plugin blocked or failing to load, the field stays
 * a working tel input and the server still normalises what it receives.
 *
 * EXPORTED because site/visits.js mounts the same FormRenderer behind a gate of
 * its own and needs the identical treatment. A second copy would be a second
 * place to remember the `input.iti` guard.
 */
export function enhancePhoneFields(root) {
    root.querySelectorAll('.sisf-el--phone input[type="tel"]').forEach((input) => {
        // Idempotent: attachPhoneWidget hangs the plugin on the element, so an
        // input that already has one is skipped rather than wrapped twice.
        if (input.iti) {
            return;
        }

        try {
            attachPhoneWidget(input);
        } catch (error) {
            console.warn('[site] could not attach the phone widget', error);
        }
    });
}

/**
 * Keep every phone field enhanced, not just the ones present at mount.
 *
 * A MULTI-PAGE FORM DESTROYS AND REBUILDS ITS FIELDS ON EVERY STEP. Attaching
 * once after mount therefore covered page one and nothing else: step forward and
 * back, and the phone input is a NEW element with no plugin on it — no country
 * selector, and the answer goes up as the national number the visitor typed,
 * which PhoneFormatter::e164() cannot parse and App\Rules\PhoneNumber rejects.
 * The visitor is told their own correctly-entered number is invalid, and only on
 * a form long enough to have steps.
 *
 * An observer rather than a hook on `onstep`: this fires when the node actually
 * appears, so it needs no guess about when Svelte has flushed, and it covers any
 * other path that swaps fields in — a conditional field, a re-render, a page the
 * renderer restores from the stack.
 *
 * The observer is RETURNED because the caller has to disconnect it when it takes
 * the renderer down: each mount makes a new one, and two watching the same root
 * would scan it twice for every mutation. Undefined where MutationObserver is
 * not implemented, which is why callers disconnect optionally.
 */
export function watchPhoneFields(root) {
    if (typeof MutationObserver !== 'function') {
        return;
    }

    const observer = new MutationObserver(() => enhancePhoneFields(root));

    observer.observe(root, { childList: true, subtree: true });

    return observer;
}
