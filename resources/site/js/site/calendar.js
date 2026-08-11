import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import arLocale from '@fullcalendar/core/locales/ar';

/**
 * FullCalendar with the plugins this site actually uses. v6 injects its own
 * styles, so there is no stylesheet to import alongside it.
 */
export function createCalendar(el, options = {}) {
    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        locales: [arLocale],
        direction: document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr',
        height: 'auto',
        ...options,
    });

    calendar.render();

    return calendar;
}

/**
 * Read-only event calendar (the Events page).
 *
 *   <div data-calendar
 *        data-events='[{"title":…,"start":…,"url":…}]'
 *        data-initial-date="2026-08-01"></div>
 *
 * MOVING THE VIEW RELOADS THE PAGE. EventsController only loaded the events that
 * overlap `initial-date`'s month, so letting FullCalendar page on its own would
 * draw an empty grid for every other month and read as "this school has no
 * events". `datesSet` fires after any navigation, so hooking it there catches
 * prev, next and today alike — and leaves FullCalendar's OWN buttons in place,
 * with their own chevron icons and styling, rather than replacing them with
 * customButtons that have neither.
 *
 * The month is taken off the view rather than from a prebuilt URL, so one
 * handler covers every direction, and `location.href` carries the locale prefix
 * with it.
 */
export default function initCalendars(root = document) {
    root.querySelectorAll('[data-calendar]').forEach((el) => {
        // datesSet also fires on the FIRST render. Navigating on that would
        // reload the page on load, forever.
        let rendered = false;

        createCalendar(el, {
            initialView: 'dayGridMonth',
            initialDate: el.dataset.initialDate || undefined,
            headerToolbar: { left: 'prev,next', center: 'title', right: '' },
            locale: document.documentElement.lang,
            events: JSON.parse(el.dataset.events || '[]'),
            datesSet(info) {
                if (!rendered) {
                    rendered = true;

                    return;
                }

                // currentStart is the first day of the month now in view, in
                // local time — getMonth() is 0-based.
                const start = info.view.currentStart;
                const month = `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}`;

                const url = new URL(window.location.href);
                url.searchParams.set('month', month);

                window.location.assign(url);
            },
        });
    });
}
