/**
 * Wording for the dashboard.
 *
 * THE SERVER RETURNS MACHINE FACTS; THIS FILE WORDS THEM. DashboardMetrics
 * hands back a route name, a MorphType alias and a locale code and stops there —
 * exactly as ActivitiesController hands back (log_name, event) and lib/activity.js
 * turns it into a sentence. The admin UI is English-only and hardcoded, so the
 * English lives here in the component layer and never in `lang/`.
 */

import { FACILITY_RESERVATION_STATUS_LABELS, FACILITY_RESERVATION_STATUS_VARIANTS } from '@/lib/facilityReservation';
import { SUBMISSION_STATUS_LABELS, SUBMISSION_STATUS_VARIANTS } from '@/lib/form';
import { JOB_APPLICATION_STATUS_LABELS, JOB_APPLICATION_STATUS_VARIANTS } from '@/lib/jobApplication';
import { VISIT_RESERVATION_STATUS_LABELS, VISIT_RESERVATION_STATUS_VARIANTS } from '@/lib/visitReservation';

/**
 * Public routes that have no content row behind them.
 *
 * A detail route (`articles.show`) gets its title from the record itself, so it
 * is absent here. These are the listings and the fixed pages, where the route
 * name IS the whole identity.
 */
export const ROUTE_LABELS = {
    home: 'Home',
    'articles.index': 'News',
    'albums.index': 'Photo albums',
    'events.index': 'Events',
    'achievements.index': 'Achievements',
    'brands.index': 'Brands',
    'jobs.index': 'Careers',
    'visits.index': 'Book a visit',
    'facilities.index': 'Facilities',
    'facilities.contact': 'Facility enquiry',
    'facilities.reserve': 'Facility booking',
    calendars: 'Calendars',
    newsletters: 'Newsletters',
    guidelines: 'Guidelines',
    documents: 'Documents',
    contact: 'Contact us',
    inquiries: 'Admissions enquiry',
};

/** MorphType alias -> what to call that kind of thing in a list. */
export const CONTENT_TYPE_LABELS = {
    page: 'Page',
    article: 'News',
    achievement: 'Achievement',
    album: 'Album',
    event: 'Event',
    job_offer: 'Job',
    program: 'Programme',
    brand: 'Brand',
    facility: 'Facility',
    form: 'Form',
};

/** How somebody arrived. Mirrors Acquisition::entryPoint(). */
export const ENTRY_POINT_LABELS = {
    direct: 'Direct',
    search: 'Search',
    social: 'Social',
    referral: 'Referral',
    campaign: 'Campaign',
    internal: 'Internal',
    unknown: 'Unknown',
};

/** The nine catalogue locales, by code. */
export const LOCALE_LABELS = {
    en: 'English',
    ar: 'Arabic',
    fr: 'French',
    es: 'Spanish',
    de: 'German',
    it: 'Italian',
    pt: 'Portuguese',
    ru: 'Russian',
    hi: 'Hindi',
    unknown: 'Unknown',
};

/**
 * The four activity sections, in the order they appear.
 *
 * `key` matches what DashboardMetrics returns — a section the signed-in admin
 * may not read is ABSENT from the payload rather than empty, so rendering is
 * driven by presence and nothing here needs a permission of its own.
 */
export const ACTIVITY_SECTIONS = [
    { key: 'submissions', label: 'Form submissions', icon: 'ki-questionnaire-tablet', labels: SUBMISSION_STATUS_LABELS, variants: SUBMISSION_STATUS_VARIANTS },
    { key: 'applications', label: 'Job applications', icon: 'ki-briefcase', labels: JOB_APPLICATION_STATUS_LABELS, variants: JOB_APPLICATION_STATUS_VARIANTS },
    { key: 'visit_reservations', label: 'Visit bookings', icon: 'ki-calendar-tick', labels: VISIT_RESERVATION_STATUS_LABELS, variants: VISIT_RESERVATION_STATUS_VARIANTS },
    { key: 'facility_reservations', label: 'Facility bookings', icon: 'ki-home-2', labels: FACILITY_RESERVATION_STATUS_LABELS, variants: FACILITY_RESERVATION_STATUS_VARIANTS },
];

/**
 * What to call one row of the top-content table.
 *
 * The record's own title wins; a listing route falls back to ROUTE_LABELS; and
 * anything unrecognised falls back to the path, which is always populated. The
 * last step matters because a route added to the site but not to ROUTE_LABELS
 * should read as a URL rather than as a blank cell.
 */
export function contentLabel(row) {
    return row?.title || ROUTE_LABELS[row?.route_name] || row?.path || row?.route_name || '—';
}

/** The kind of thing a row is, or null for a listing/fixed page. */
export function contentType(row) {
    return row?.type ? (CONTENT_TYPE_LABELS[row.type] ?? row.type) : null;
}

export function entryPointLabel(value) {
    return ENTRY_POINT_LABELS[value] ?? value ?? '—';
}

export function localeLabel(value) {
    return LOCALE_LABELS[value] ?? value ?? '—';
}
