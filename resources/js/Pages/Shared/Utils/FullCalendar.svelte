<script>
    import { onMount, onDestroy, createEventDispatcher, tick } from 'svelte';

    export let events = [];
    export let options = {};
    export let height = 'auto';
    export let initialView = 'dayGridMonth';
    export let themeColor = '#001965';

    let calendarElement;
    let calendarInstance;

    const dispatch = createEventDispatcher();

    function buildOptions() {
        return {
            initialView,
            height,
            firstDay: 1,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek',
            },
            events,
            selectable: false,
            dayMaxEvents: true,
            fixedWeekCount: false,
            dateClick(info) {
                dispatch('dateClick', {
                    date: info.dateStr,
                    dateObj: info.date,
                    allDay: info.allDay,
                    dayEl: info.dayEl,
                });
            },
            datesSet(info) {
                dispatch('datesSet', {
                    start: info.startStr,
                    end: info.endStr,
                    startDate: info.start,
                    endDate: info.end,
                    view: info.view,
                });
            },
            eventClick(info) {
                info.jsEvent?.preventDefault();
                info.jsEvent?.stopPropagation();

                const start = info.event.start;
                const date = info.event.startStr
                    || (start instanceof Date
                        ? `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}-${String(start.getDate()).padStart(2, '0')}`
                        : '');

                dispatch('eventClick', {
                    event: info.event,
                    date,
                    extendedProps: info.event.extendedProps,
                    jsEvent: info.jsEvent,
                });
            },
            select(info) {
                dispatch('select', {
                    start: info.startStr,
                    end: info.endStr,
                    startDate: info.start,
                    endDate: info.end,
                    allDay: info.allDay,
                });
            },
            ...options,
        };
    }

    function syncEvents() {
        if (!calendarInstance) {
            return;
        }

        calendarInstance.removeAllEvents();
        events.forEach((event) => calendarInstance.addEvent(event));
    }

    onMount(() => {
        if (typeof window === 'undefined' || !window.FullCalendar) {
            console.warn('FullCalendar is not loaded. Please include the FullCalendar library.');
            return;
        }

        calendarInstance = new window.FullCalendar.Calendar(calendarElement, buildOptions());
        calendarInstance.render();
        dispatch('ready', { calendar: calendarInstance });
    });

    onDestroy(() => {
        if (calendarInstance) {
            calendarInstance.destroy();
            calendarInstance = null;
        }
    });

    $: if (calendarInstance) {
        events;
        syncEvents();
        tick().then(() => calendarInstance?.updateSize());
    }

    $: if (calendarInstance && options) {
        calendarInstance.setOption('selectable', options.selectable ?? false);
        calendarInstance.setOption('selectMirror', options.selectMirror ?? false);
        calendarInstance.setOption('unselectAuto', options.unselectAuto ?? true);
        calendarInstance.setOption('selectMinDistance', options.selectMinDistance ?? 0);
    }

    export function getCalendar() {
        return calendarInstance;
    }

    export function updateSize() {
        calendarInstance?.updateSize();
    }

    export function gotoDate(date) {
        calendarInstance?.gotoDate(date);
    }

    export function changeView(viewName) {
        calendarInstance?.changeView(viewName);
    }

    export function refetchEvents() {
        syncEvents();
    }
</script>

<div
    bind:this={calendarElement}
    class="mawdja-fullcalendar"
    style="--fc-brand-color: {themeColor};"
></div>

<style>
    :global(.mawdja-fullcalendar .fc) {
        --fc-border-color: #e2e8f0;
        --fc-button-bg-color: var(--fc-brand-color);
        --fc-button-border-color: var(--fc-brand-color);
        --fc-button-hover-bg-color: #002680;
        --fc-button-hover-border-color: #002680;
        --fc-button-active-bg-color: #00124d;
        --fc-button-active-border-color: #00124d;
        --fc-today-bg-color: rgba(0, 25, 101, 0.08);
        --fc-event-border-color: var(--fc-brand-color);
        --fc-page-bg-color: #ffffff;
        font-family: inherit;
    }

    :global(.mawdja-fullcalendar .fc .fc-toolbar-title) {
        font-size: 1.125rem;
        font-weight: 600;
        color: #0f172a;
    }

    :global(.mawdja-fullcalendar .fc .fc-button) {
        border-radius: 0.5rem;
        font-size: 0.8125rem;
        font-weight: 500;
        padding: 0.35rem 0.75rem;
        text-transform: capitalize;
        box-shadow: none;
    }

    :global(.mawdja-fullcalendar .fc .fc-button:focus) {
        box-shadow: 0 0 0 3px rgba(0, 25, 101, 0.15);
    }

    :global(.mawdja-fullcalendar .fc .fc-col-header-cell-cushion),
    :global(.mawdja-fullcalendar .fc .fc-daygrid-day-number) {
        color: #334155;
        font-weight: 500;
        text-decoration: none;
    }

    :global(.mawdja-fullcalendar .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number) {
        background-color: var(--fc-brand-color);
        color: #ffffff;
        border-radius: 9999px;
        width: 1.75rem;
        height: 1.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    :global(.mawdja-fullcalendar .fc .fc-daygrid-day-frame) {
        min-height: 5.5rem;
        cursor: pointer;
    }

    :global(.mawdja-fullcalendar .fc .fc-daygrid-day:hover .fc-daygrid-day-frame) {
        background-color: rgba(0, 25, 101, 0.04);
    }

    :global(.mawdja-fullcalendar .fc .fc-daygrid-event) {
        border-radius: 0.375rem;
        font-size: 0.6875rem;
        font-weight: 600;
        margin-top: 0.125rem;
        padding: 0.125rem 0.375rem;
    }

    :global(.mawdja-fullcalendar .fc .fc-daygrid-block-event .fc-event-main) {
        padding: 0.25rem 0.375rem;
    }

    :global(.mawdja-fullcalendar .fc .fc-daygrid-day-events) {
        margin-bottom: 0;
        min-height: 1.75rem;
    }
</style>
