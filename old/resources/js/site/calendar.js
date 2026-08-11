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
 *   <div data-calendar data-events='[{"title":…,"start":…,"url":…}]'></div>
 */
export default function initCalendars(root = document) {
    root.querySelectorAll('[data-calendar]').forEach((el) => {
        createCalendar(el, {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next', center: 'title', right: '' },
            locale: document.documentElement.lang,
            events: JSON.parse(el.dataset.events || '[]'),
        });
    });
}
