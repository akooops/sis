/**
 * FullCalendar, for the admin.
 *
 * A SECOND, DELIBERATE COPY of what resources/site/js/site/calendar.js does for
 * the public site. Nothing under resources/admin may import from resources/site or
 * the two bundles start sharing modules, and this file is fifteen lines — cheaper
 * than the boundary violation, and the two have different defaults anyway: the
 * admin edits, so it needs the interaction plugin's selection and the site does
 * not.
 *
 * Callers reach it through a dynamic import(), so FullCalendar's ~250 kB is a
 * chunk of its own that only the time-slots page downloads.
 */
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';

/**
 * Build and render a calendar into `el`.
 *
 * Direction follows the admin shell rather than being passed in: the theme owns
 * <html dir> and a calendar that disagreed with it would put its Next button on
 * the wrong side of the toolbar.
 */
export function createCalendar(el, options = {}) {
    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        direction: document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr',
        // The card grows with the month instead of scrolling inside a fixed box,
        // which is what makes a 31-day month with three tours a day readable.
        height: 'auto',
        ...options,
    });

    calendar.render();

    return calendar;
}
