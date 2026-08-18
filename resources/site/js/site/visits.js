/**
 * The /visits booking wizard.
 *
 * THREE STEPS, ONE ON SCREEN AT A TIME — pick a visit, pick a time, give your
 * details. The steps are three Blade sections and moving between them toggles
 * `hidden`, so a step that is not showing is out of the tab order and out of the
 * accessibility tree rather than merely scrolled past.
 *
 * Modelled on site/forms.js, and it reuses that module's two load-bearing ideas.
 *
 * ONE HANDLE, EVERY MOUNT THROUGH clear(). Going back and forward must never
 * stack two renderers into the same root.
 *
 * MOUNT THE RENDERER WITH `values`. FormRenderer's `values` prop is INITIAL — the
 * component owns its answers after that, and `posted` is derived from them, so
 * writing the chosen slot into the hidden mirrors from here would be overwritten
 * on the next render. This is the same seam ApplyChoice uses to hand the renderer
 * a parsed CV: gate first, then mount with what the gate produced.
 *
 * WHY THE GATE IS NEEDED AT ALL. The jobs page can preset its hidden posting id
 * server-side, because that page IS one posting. This page is every visit, and
 * which one plus which time are chosen in the browser after the page has
 * rendered — so the form cannot exist until they have been.
 *
 * Nothing here trusts the browser: the three hidden answers are re-read by
 * VisitReservationIsAllowed at submit and again by ReservationProjector
 * afterwards. This only saves an honest visitor from typing an id.
 */
import { mount, unmount } from 'svelte';
import FormRenderer from '@site/components/forms/FormRenderer.svelte';
import { collectEnvironment, createTelemetry } from '@site/lib/forms/telemetry';
import VisitSlots from './islands/VisitSlots.svelte';
import { enhancePhoneFields, readPayload, watchPhoneFields } from './forms';

export default function initVisits() {
    const root = document.querySelector('[data-visits-root]');

    if (!root) {
        return;
    }

    const formRoot = root.querySelector('[data-sisf-root]');
    const schema = readPayload('sisf-schema');
    const config = readPayload('sisf-config') ?? {};
    const visits = readPayload('visits-config') ?? {};

    if (!formRoot || !schema) {
        return;
    }

    const labels = visits.labels ?? {};
    const keys = {
        service: visits.serviceField ?? 'visit_service_id',
        slot: visits.slotField ?? 'visit_slot_id',
        visitors: visits.visitorsField ?? 'visitors_count',
        students: visits.studentsField ?? 'students',
    };

    const slotsTemplate = root.dataset.slotsUrl ?? '';
    const sections = new Map(
        [...root.querySelectorAll('[data-visit-step]')].map((el) => [Number(el.dataset.visitStep), el]),
    );
    const markers = new Map(
        [...root.querySelectorAll('[data-visit-step-marker]')].map((el) => [Number(el.dataset.visitStepMarker), el]),
    );
    const calendarHost = root.querySelector('[data-visit-calendar]');

    const environment = collectEnvironment();
    const telemetry = createTelemetry({
        endpoint: config.telemetryAction ?? null,
        token: config.token ?? null,
        capture: config.capture ?? {},
    });

    /** The renderer, or nothing. Never two. */
    let mountedForm = null;
    /** The calendar, or nothing. Torn down when the visit changes. */
    let mountedCalendar = null;
    /** Disconnected before every unmount; watchPhoneFields makes a fresh one. */
    let phoneObserver = null;
    /** See showForm() — attach() may run exactly once per page load. */
    let telemetryAttached = false;

    /** {id, slug, title, max} */
    let service = null;
    /** {id, start} */
    let slot = null;
    let visitors = 1;
    let step = Number(root.dataset.step) || 1;

    wireCards();
    wireBackButtons();

    /*
     * A REJECTED SUBMIT SKIPS THE WIZARD. The visitor has already chosen a visit
     * and a time, `config.old` is carrying both along with everything they typed,
     * and walking them back through two steps would throw all of it away. Blade
     * has already opened the page on step three and filled the card's header from
     * the same old().
     */
    if (config.returning) {
        const old = config.old ?? {};

        service = serviceFromId(old[keys.service]);
        slot = old[keys.slot] ? { id: old[keys.slot], start: null } : null;
        visitors = Math.max(1, Number(old[keys.visitors] ?? 1) || 1);

        // Built so that going Back lands on a calendar with their time still ringed,
        // rather than on an empty card.
        showCalendar();
        showForm({ ...old });
    }

    /* ------------------------------------------------------------------
     Moving between steps
    ------------------------------------------------------------------*/

    /**
     * Show one step and hide the rest.
     *
     * `hidden` rather than a class: a step that is not showing must be out of the
     * tab order too, or a visitor tabbing through step one walks into the form on
     * step three without ever seeing it.
     */
    function goTo(next) {
        step = next;
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

    function wireBackButtons() {
        root.querySelectorAll('[data-visit-back]').forEach((button) => {
            button.addEventListener('click', () => goTo(Number(button.dataset.visitBack) || 1));
        });
    }

    /* ------------------------------------------------------------------
     Step 1 — the cards
    ------------------------------------------------------------------*/

    function wireCards() {
        root.querySelectorAll('[data-visit-service]').forEach((card) => {
            const input = card.querySelector('[data-visit-count]');
            const max = Math.max(1, Number(card.dataset.visitMax ?? 1) || 1);

            const bump = (by) => {
                // Clamped to the SERVICE, not to a constant: a school running a
                // family-only tour sets 2 and a group visit sets 30, and the
                // submit-time rule clamps to the same number.
                const next = Math.min(max, Math.max(1, Number(input.value ?? 1) + by));

                input.value = String(next);

                if (service?.id === card.dataset.visitService) {
                    visitors = next;
                    paintHeaders();
                }
            };

            card.querySelector('[data-visit-step-up]')?.addEventListener('click', () => bump(1));
            card.querySelector('[data-visit-step-down]')?.addEventListener('click', () => bump(-1));

            card.querySelector('[data-visit-select]')?.addEventListener('click', () => {
                selectService(card, Math.max(1, Number(input?.value ?? 1) || 1));
            });
        });
    }

    function serviceFromId(id) {
        const card = id ? root.querySelector(`[data-visit-service="${CSS.escape(String(id))}"]`) : null;

        return card ? readCard(card) : null;
    }

    function readCard(card) {
        return {
            id: card.dataset.visitService,
            slug: card.dataset.visitSlug,
            title: card.dataset.visitTitle ?? '',
            max: Math.max(1, Number(card.dataset.visitMax ?? 1) || 1),
        };
    }

    function selectService(card, count) {
        const next = readCard(card);
        const changed = next.id !== service?.id;

        service = next;
        visitors = Math.min(next.max, count);

        /*
         * A DIFFERENT VISIT INVALIDATES THE TIME. Slots belong to one visit, and
         * the submit-time rule refuses a slot attached to another — so carrying the
         * old choice forward would only produce a rejection the visitor could not
         * see the cause of.
         */
        if (changed) {
            slot = null;
            clearForm();
        }

        paintHeaders();
        showCalendar();
        goTo(2);
    }

    /* ------------------------------------------------------------------
     Step 2 — the calendar
    ------------------------------------------------------------------*/

    function showCalendar() {
        if (!calendarHost || !service) {
            return;
        }

        if (mountedCalendar) {
            unmount(mountedCalendar);
            mountedCalendar = null;
        }

        mountedCalendar = mount(VisitSlots, {
            target: calendarHost,
            props: {
                // __SERVICE__ is the placeholder route() left in the template, so
                // the feed keeps whatever locale prefix the page was rendered under.
                slotsUrl: slotsTemplate.replace('__SERVICE__', encodeURIComponent(service.slug)),
                locale: visits.locale ?? 'en',
                direction: visits.direction ?? 'ltr',
                labels,
                selectedId: slot?.id ?? null,
                onselect: chooseSlot,
            },
        });
    }

    function chooseSlot(picked) {
        slot = { id: picked.id, start: picked.start };

        paintHeaders();

        /*
         * REMOUNT WITH WHAT IS ALREADY TYPED. `values` is an initial prop, so
         * changing the time means a new mount — and reading the answers back out of
         * the renderer's own hidden mirrors is what stops that from wiping a form
         * the visitor has half filled in on a previous visit to step three.
         */
        showForm({ ...(readAnswers() ?? config.old ?? {}) });

        goTo(3);
    }

    /* ------------------------------------------------------------------
     Step 3 — the form
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
                    // Last, so the wizard's answers always win over a stale copy in
                    // `old` — the visitor changing their time is the whole point.
                    [keys.service]: service?.id ?? '',
                    [keys.slot]: slot?.id ?? '',
                    [keys.visitors]: String(visitors),
                    [keys.students]: studentRows(values[keys.students]),
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
     * As many blank student rows as the visitor asked for, keeping anything they
     * have already typed.
     *
     * The party-size counter is the CEILING of the group, not a promise: the rule
     * enforces it as a max, and a visitor who deletes a row has changed their mind
     * rather than made a mistake. Seeding the rows is only so the second page of
     * the form opens showing the number of children they said were coming.
     */
    function studentRows(existing) {
        const rows = Array.isArray(existing) ? existing.filter((row) => row && typeof row === 'object') : [];

        while (rows.length < visitors) {
            rows.push({});
        }

        return rows.slice(0, Math.max(visitors, rows.length));
    }

    /**
     * Read the renderer's answers back out of the hidden inputs it mirrors them
     * into.
     *
     * Only the two shapes THIS form produces — `fields[key]` and
     * `fields[group][index][child]`. A file or a multi-select posts differently and
     * the visit form has neither; if one is ever added here, this needs the extra
     * case rather than silently dropping it.
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

                return;
            }

            if (path.length === 3) {
                const [group, index, child] = path;

                answers[group] ??= [];
                answers[group][index] ??= {};
                answers[group][index][child] = input.value;
            }
        });

        // The group came back keyed by its original indices, which may have gaps
        // once a row was removed; the renderer wants a list.
        if (Array.isArray(answers[keys.students])) {
            answers[keys.students] = answers[keys.students].filter(Boolean);
        }

        return answers;
    }

    /* ------------------------------------------------------------------
     Chrome
    ------------------------------------------------------------------*/

    /**
     * The two card headers: the visit's name on both, and a sub-line that says how
     * many are coming on step two and adds the chosen time on step three.
     */
    function paintHeaders() {
        // `data-visit-heading`, NOT `data-visit-title`: the service CARDS carry
        // the latter as stored data, so writing to it would blank the grid.
        root.querySelectorAll('[data-visit-heading]').forEach((el) => {
            el.textContent = service?.title ?? '';
        });

        const people = `${labels.visitors ?? ''}: ${visitors}`;

        setMeta(2, service ? people : '');
        setMeta(3, service ? [slot?.start ? formatWhen(slot.start) : null, people].filter(Boolean).join(' · ') : '');
    }

    function setMeta(number, text) {
        const el = sections.get(number)?.querySelector('[data-visit-meta]');

        if (el) {
            el.textContent = text;
        }
    }

    function formatWhen(date) {
        try {
            return new Intl.DateTimeFormat(visits.locale ?? 'en', {
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
