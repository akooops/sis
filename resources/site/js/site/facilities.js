/**
 * The venue booking wizard on /facilities/{slug}/reserve.
 *
 * TWO STEPS, ONE ON SCREEN AT A TIME — pick a time, then your details. Its visits
 * twin has three, because that page is every visit and has to ask which one
 * first; this page IS one venue, so the first question is already answered.
 *
 * A TRIMMED COPY of site/visits.js, and it reuses that module's imports from
 * site/forms.js rather than repeating them. The two load-bearing ideas are the
 * same:
 *
 * ONE HANDLE, EVERY MOUNT THROUGH clear(). Going back and forward must never
 * stack two renderers into the same root.
 *
 * MOUNT THE RENDERER WITH `values`. FormRenderer's `values` prop is INITIAL — the
 * component owns its answers after that, and `posted` is derived from them, so
 * writing the chosen slot into the hidden mirrors from here would be overwritten
 * on the next render.
 *
 * WHY A GATE AT ALL, when the venue is already a preset. Because the TIME is not:
 * which slot was picked happens in the browser after the page has rendered, so
 * the form cannot exist until it has been. `facility_id` needs none of this — the
 * controller presets it, which is also why the contact page needs no JavaScript.
 *
 * Nothing here trusts the browser: both hidden answers are re-read by
 * FacilityReservationIsAllowed at submit and the venue is taken off the SLOT by
 * the projector afterwards.
 */
import { mount, unmount } from 'svelte';
import FormRenderer from '@site/components/forms/FormRenderer.svelte';
import { collectEnvironment, createTelemetry } from '@site/lib/forms/telemetry';
import SlotPicker from './islands/SlotPicker.svelte';
import { enhancePhoneFields, readPayload, watchPhoneFields } from './forms';

export default function initFacilities() {
    const root = document.querySelector('[data-facility-reserve]');

    if (!root) {
        return;
    }

    const formRoot = root.querySelector('[data-sisf-root]');
    const schema = readPayload('sisf-schema');
    const config = readPayload('sisf-config') ?? {};
    const facilities = readPayload('facilities-config') ?? {};

    if (!formRoot || !schema) {
        return;
    }

    const labels = facilities.labels ?? {};
    const slotKey = facilities.slotField ?? 'facility_slot_id';

    const slotsUrl = root.dataset.slotsUrl ?? '';
    const sections = new Map(
        [...root.querySelectorAll('[data-facility-step]')].map((el) => [Number(el.dataset.facilityStep), el]),
    );
    const markers = new Map(
        [...root.querySelectorAll('[data-facility-step-marker]')].map((el) => [
            Number(el.dataset.facilityStepMarker),
            el,
        ]),
    );
    const calendarHost = root.querySelector('[data-facility-calendar]');

    const environment = collectEnvironment();
    const telemetry = createTelemetry({
        endpoint: config.telemetryAction ?? null,
        token: config.token ?? null,
        capture: config.capture ?? {},
    });

    /** The renderer, or nothing. Never two. */
    let mountedForm = null;
    /** Disconnected before every unmount; watchPhoneFields makes a fresh one. */
    let phoneObserver = null;
    /** See showForm() — attach() may run exactly once per page load. */
    let telemetryAttached = false;

    /** {id, start} */
    let slot = null;

    showCalendar();
    wireBack();

    /*
     * A REJECTED SUBMIT SKIPS THE CALENDAR. The visitor has already chosen a time,
     * `config.old` is carrying it along with everything they typed, and sending
     * them back a step would throw all of it away. Blade has already opened the
     * page on step two and filled the card header from the same old().
     */
    if (config.returning) {
        const old = config.old ?? {};

        slot = old[slotKey] ? { id: old[slotKey], start: null } : null;

        showForm({ ...old });
    }

    /* ------------------------------------------------------------------
     Moving between the two steps
    ------------------------------------------------------------------*/

    /**
     * Show one step and hide the other.
     *
     * `hidden` rather than a class: a step that is not showing must be out of the
     * tab order too, or a visitor tabbing through the calendar walks into the form
     * without ever seeing it.
     */
    function goTo(next) {
        root.dataset.step = String(next);

        sections.forEach((el, number) => {
            el.hidden = number !== next;
        });

        markers.forEach((el, number) => {
            el.classList.toggle('is-active', number === next);
            el.classList.toggle('is-done', number < next);
            el.setAttribute('aria-current', number === next ? 'step' : 'false');
        });

        // The card that just appeared is usually below the fold on a phone, and a
        // step change with nothing moving reads as a page that ignored the click.
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function wireBack() {
        root.querySelector('[data-facility-back]')?.addEventListener('click', () => goTo(1));
    }

    /* ------------------------------------------------------------------
     Step 1 — the calendar
    ------------------------------------------------------------------*/

    function showCalendar() {
        if (!calendarHost || !slotsUrl) {
            return;
        }

        mount(SlotPicker, {
            target: calendarHost,
            props: {
                slotsUrl,
                locale: facilities.locale ?? 'en',
                direction: facilities.direction ?? 'ltr',
                labels,
                selectedId: slot?.id ?? null,
                onselect: chooseSlot,
            },
        });
    }

    function chooseSlot(picked) {
        slot = { id: picked.id, start: picked.start };

        paintHeader();

        /*
         * REMOUNT WITH WHAT IS ALREADY TYPED. `values` is an initial prop, so
         * changing the time means a new mount — and reading the answers back out of
         * the renderer's own hidden mirrors is what stops that from wiping a form
         * the visitor has half filled in on a previous visit to step two.
         */
        showForm({ ...(readAnswers() ?? config.old ?? {}) });

        goTo(2);
    }

    /* ------------------------------------------------------------------
     Step 2 — the form
    ------------------------------------------------------------------*/

    function clearForm() {
        // Before the unmount, or the observer fires on the renderer's own removal
        // and re-scans a subtree already on its way out.
        phoneObserver?.disconnect();
        phoneObserver = null;

        if (mountedForm) {
            unmount(mountedForm);
            mountedForm = null;
        }
    }

    function showForm(values) {
        clearForm();

        mountedForm = mount(FormRenderer, {
            target: formRoot,
            props: {
                schema,
                mode: 'live',
                action: config.action ?? null,
                csrf: config.csrf ?? null,
                token: config.token ?? null,
                honeypot: config.honeypot ?? null,
                uploadAction: config.uploadAction ?? null,
                captcha: config.captcha ?? null,
                labels: config.labels ?? null,
                values: {
                    ...values,
                    // Last, so the wizard's answer always wins over a stale copy in
                    // `old` — the visitor changing their time is the whole point.
                    // `facility_id` is absent here on purpose: the controller
                    // presets it and `values` already carries it through
                    // config.old, so restating it would be a second source.
                    [slotKey]: slot?.id ?? '',
                },
                errors: { ...(config.errors ?? {}) },
                chrome: config.chrome ?? false,
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
         * remount would report every interaction twice. Attaching once is enough
         * because the listeners are delegated off `formRoot`, the Blade shell's own
         * div, which outlives every mount.
         */
        if (!telemetryAttached) {
            telemetryAttached = true;
            telemetry.attach(formRoot);
        }

        enhancePhoneFields(formRoot);
        phoneObserver = watchPhoneFields(formRoot);
    }

    /**
     * Read the renderer's answers back out of the hidden inputs it mirrors them
     * into.
     *
     * Only the `fields[key]` shape, which is all this form produces — it has no
     * repeatable group and no file field. If one is ever added here, this needs the
     * nested case its visits twin carries rather than silently dropping it.
     */
    function readAnswers() {
        const form = formRoot.querySelector('form.sisf-form');

        if (!form) {
            return null;
        }

        const answers = {};

        form.querySelectorAll('input[name^="fields["]').forEach((input) => {
            const path = [...input.name.matchAll(/\[([^\]]*)\]/g)].map((match) => match[1]);

            if (path.length === 1) {
                answers[path[0]] = input.value;
            }
        });

        return answers;
    }

    /* ------------------------------------------------------------------
     Chrome
    ------------------------------------------------------------------*/

    function paintHeader() {
        const el = sections.get(2)?.querySelector('[data-facility-meta]');

        if (!el) {
            return;
        }

        el.textContent = slot?.start ? `${labels.selected ?? ''}: ${formatWhen(slot.start)}` : '';
    }

    function formatWhen(date) {
        try {
            return new Intl.DateTimeFormat(facilities.locale ?? 'en', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }).format(date);
        } catch {
            return String(date);
        }
    }
}
