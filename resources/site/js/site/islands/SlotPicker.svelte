<script>
    /**
     * The slot picker: a month of times for one bookable thing, a window at a time.
     *
     * DOMAIN-AGNOSTIC ON PURPOSE — it takes a URL, some labels and a callback, and
     * knows nothing about what is being booked. Both /visits and
     * /facilities/{slug}/reserve mount it, which is why it is named for what it
     * does rather than for the first module that needed it.
     *
     * REPLACES VisitBooking.svelte, which was the old app's whole three-step
     * wizard ported across and never wired up. Two things are different here and
     * both matter. Its events came from a data-attribute holding EVERY upcoming
     * slot for EVERY service, serialised into the page — so a school with a term
     * of times shipped all of them to every visitor, and a slot that filled while
     * the page was open still read as available. And its step three was a bespoke
     * form posting JSON to an endpoint with no captcha, no honeypot and no IP
     * block. This component does one job: pick a time. The form is the seeded one.
     *
     * The feed is fetched on `datesSet`, which fires on the first render and on
     * every month change, so navigation costs one request for the month being
     * looked at and nothing else.
     */
    import { onDestroy } from 'svelte';

    let {
        slotsUrl = '',
        locale = 'en',
        direction = 'ltr',
        labels = {},
        selectedId = null,
        onselect = null,
    } = $props();

    let host = $state(null);
    let calendar = null;
    let loading = $state(false);
    let error = $state('');

    /** The slot ids currently drawn, so a re-fetch can re-apply the selection. */
    let chosen = $state(selectedId);

    const STATE_CLASSES = {
        open: 'is-available',
        limited: 'is-limited',
        full: 'is-full',
        closed: 'is-closed',
    };

    /**
     * Build the calendar once the host div exists, and tear it down with the
     * component.
     *
     * The import is dynamic so FullCalendar (~250 kB) is fetched only by a visitor
     * who actually picked a visit, not by everyone who opens the page.
     */
    $effect(() => {
        if (!host || calendar) {
            return;
        }

        let live = true;

        import('../calendar').then(({ createCalendar }) => {
            if (!live) {
                return;
            }

            calendar = createCalendar(host, {
                locale,
                direction,
                initialView: 'dayGridMonth',
                headerToolbar: { left: 'prev,next', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                slotMinTime: '06:00:00',
                slotMaxTime: '20:00:00',
                allDaySlot: false,
                // A month of tour slots is a lot of small events; without this a
                // busy day collapses into "+7 more" and the visitor cannot see a
                // single time without clicking twice.
                dayMaxEvents: 4,
                events: (info, success, failure) => load(info, success, failure),
                eventClick: (info) => choose(info),
            });
        });

        return () => {
            live = false;
        };
    });

    onDestroy(() => {
        calendar?.destroy();
        calendar = null;
    });

    /**
     * FullCalendar's own feed function: it hands us the visible window and takes
     * the events back, so month navigation needs no listener of its own.
     */
    async function load(info, success, failure) {
        if (!slotsUrl) {
            success([]);

            return;
        }

        loading = true;
        error = '';

        try {
            const url = new URL(slotsUrl, window.location.origin);

            url.searchParams.set('start', info.startStr);
            url.searchParams.set('end', info.endStr);

            const response = await fetch(url, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const body = await response.json();

            success((body?.data ?? []).map(toEvent));
        } catch (cause) {
            error = labels.loadFailed ?? 'Could not load the available times.';
            failure?.(cause);
        } finally {
            loading = false;
        }
    }

    /**
     * One slot as a calendar event.
     *
     * The title carries the count because the colour alone cannot say HOW full
     * something is — "3 places left" is what makes somebody book today. `:count`
     * is interpolated here rather than server-side because the number is per slot.
     */
    function toEvent(slot) {
        const remaining = Number(slot.remaining ?? 0);
        const bookable = slot.state === 'open' || slot.state === 'limited';

        const title = bookable
            ? String(labels.remaining ?? ':count').replace(':count', String(remaining))
            : (labels[slot.state] ?? '');

        const classNames = [STATE_CLASSES[slot.state] ?? 'is-closed'];

        if (slot.id === chosen) {
            classNames.push('is-chosen');
        }

        return {
            id: String(slot.id),
            start: slot.start,
            end: slot.end,
            title,
            classNames,
            extendedProps: {
                state: slot.state,
                remaining,
                capacity: Number(slot.capacity ?? 0),
                bookable,
            },
        };
    }

    /**
     * A click on a time.
     *
     * A full or closed slot is REFUSED rather than ignored: silently doing nothing
     * reads as a broken page, and the sentence explains which of the two it was.
     */
    function choose(info) {
        const props = info.event.extendedProps;

        if (!props.bookable) {
            error = labels[props.state] ?? labels.full ?? '';

            return;
        }

        error = '';
        chosen = info.event.id;

        // Repaint so the previous choice loses its ring and this one gains it.
        // refetchEvents rather than a manual class edit: the feed is the only
        // thing that knows what else changed while the visitor was deciding.
        calendar?.refetchEvents();

        onselect?.({
            id: info.event.id,
            start: info.event.start,
            end: info.event.end,
            remaining: props.remaining,
            capacity: props.capacity,
        });
    }
</script>

<div class="mb-5 flex flex-wrap items-center justify-center gap-5 text-sm">
    <span class="flex items-center gap-2">
        <span class="h-4 w-4 rounded-xs bg-slot-open"></span>
        <span>{labels.available ?? ''}</span>
    </span>
    <span class="flex items-center gap-2">
        <span class="h-4 w-4 rounded-xs bg-slot-limited"></span>
        <span>{labels.limited ?? ''}</span>
    </span>
    <span class="flex items-center gap-2">
        <span class="h-4 w-4 rounded-xs bg-slot-full"></span>
        <span>{labels.full ?? ''}</span>
    </span>
    <span class="flex items-center gap-2">
        <span class="h-4 w-4 rounded-xs bg-slot-closed"></span>
        <span>{labels.closed ?? ''}</span>
    </span>
</div>

{#if error}
    <div class="alert alert-warning mb-4 flex items-center gap-2" role="alert">
        <i class="uil uil-exclamation-triangle" aria-hidden="true"></i>
        <span class="flex-1">{error}</span>
    </div>
{/if}

<div class="relative">
    {#if loading}
        <span
            class="absolute end-2 top-2 z-10 inline-block h-4 w-4 animate-spin rounded-full border-2 border-brand border-e-transparent"
            aria-hidden="true"
        ></span>
    {/if}

    <div bind:this={host}></div>
</div>
