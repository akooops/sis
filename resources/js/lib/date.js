/**
 * Timezone-aware date/time formatting.
 *
 * The API sends timestamps in ISO 8601 / UTC (e.g. "2026-07-13T12:00:00Z").
 * `Intl.DateTimeFormat` renders in the *browser's* timezone and locale by
 * default, so the displayed hour is always the viewer's real local time.
 *
 * Every timestamp shown in the UI should go through these helpers so the
 * formatting stays consistent across the whole app.
 */

/**
 * The locale every formatter falls back to when a caller doesn't pass one. The
 * UI is English-only, so this is fixed rather than switchable.
 * @type {string}
 */
const activeLocale = 'en';

/**
 * Parse an ISO string / Date / epoch into a valid Date, or null when the
 * input is empty or unparseable.
 * @param {string | number | Date | null | undefined} value
 * @returns {Date | null}
 */
function toDate(value) {
    if (value === null || value === undefined || value === '') return null;
    const date = value instanceof Date ? value : new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
}

/**
 * Format with a given Intl options object, returning `fallback` for
 * null/invalid input.
 * @param {string | number | Date | null | undefined} value
 * @param {Intl.DateTimeFormatOptions} options
 * @param {string} [fallback]
 * @param {string} [locale]
 */
function format(value, options, fallback = '—', locale) {
    const date = toDate(value);
    if (!date) return fallback;
    try {
        return new Intl.DateTimeFormat(locale ?? activeLocale, options).format(date);
    } catch {
        return date.toLocaleString();
    }
}

/** e.g. "13 Jul 2026" */
export function formatDate(value, { fallback = '—', locale } = {}) {
    return format(value, { day: '2-digit', month: 'short', year: 'numeric' }, fallback, locale);
}

/** e.g. "13 Jul 2026, 14:32" (local time) */
export function formatDateTime(value, { fallback = '—', locale } = {}) {
    return format(
        value,
        { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' },
        fallback,
        locale,
    );
}

/** e.g. "14:32" (local time) */
export function formatTime(value, { fallback = '—', locale } = {}) {
    return format(value, { hour: '2-digit', minute: '2-digit' }, fallback, locale);
}

/**
 * Relative time, e.g. "3 hours ago" / "in 2 days".
 * @param {string | number | Date | null | undefined} value
 */
export function formatRelative(value, { fallback = '—', locale } = {}) {
    const date = toDate(value);
    if (!date) return fallback;

    const diffMs = date.getTime() - Date.now();
    const abs = Math.abs(diffMs);

    /** @type {[Intl.RelativeTimeFormatUnit, number][]} */
    const units = [
        ['year', 1000 * 60 * 60 * 24 * 365],
        ['month', 1000 * 60 * 60 * 24 * 30],
        ['week', 1000 * 60 * 60 * 24 * 7],
        ['day', 1000 * 60 * 60 * 24],
        ['hour', 1000 * 60 * 60],
        ['minute', 1000 * 60],
        ['second', 1000],
    ];

    try {
        const rtf = new Intl.RelativeTimeFormat(locale ?? activeLocale, { numeric: 'auto' });
        for (const [unit, ms] of units) {
            if (abs >= ms || unit === 'second') {
                return rtf.format(Math.round(diffMs / ms), unit);
            }
        }
    } catch {
        // fall through
    }
    return formatDateTime(date, { fallback, locale });
}
