/**
 * Facility status — mirrors App\States\Facility\FacilityStatus. Only
 * `published` is served.
 *
 * `scheduled` is a promise the server keeps once published_at passes.
 *
 * Note this says nothing about whether anyone can BOOK: that is derived from the
 * venue's slots, so a published venue with no upcoming times still shows on the
 * site and says it has none.
 */
export const FACILITY_STATUS_LABELS = {
    draft: 'Draft',
    scheduled: 'Scheduled',
    published: 'Published',
    hidden: 'Hidden',
};

/** Badge variant per status — only `published` is a success. */
export const FACILITY_STATUS_VARIANTS = {
    draft: 'secondary',
    scheduled: 'warning',
    published: 'success',
    hidden: 'destructive',
};

/**
 * Mirrors FacilityStatus::config(). The 422 stays the enforcement — this only
 * stops the form offering a door the server has locked.
 */
export const FACILITY_STATUS_TRANSITIONS = {
    draft: ['scheduled', 'published'],
    scheduled: ['draft', 'published', 'hidden'],
    published: ['hidden', 'scheduled'],
    hidden: ['draft', 'scheduled', 'published'],
};

/** Statuses reachable from `current`, including staying put. Create has no current. */
export function reachableStatuses(current = null) {
    if (!current) return ['draft', 'scheduled', 'published'];

    return [current, ...(FACILITY_STATUS_TRANSITIONS[current] ?? [])];
}

/**
 * Only a schedule needs a date from the user. Publishing is always "now" — the
 * server stamps published_at — so asking for it would be a field with one correct
 * answer.
 */
export function needsPublishedAt(status) {
    return status === 'scheduled';
}

/**
 * Mirrors FacilitySlot::STATES, and FacilitySlot::state() is what decides which one a
 * slot is in. `limited` is BOOKABLE — it means nearly full, not refused.
 */
export const SLOT_STATE_LABELS = {
    open: 'Open',
    limited: 'Almost full',
    full: 'Fully booked',
    closed: 'Closed',
};

export const SLOT_STATE_VARIANTS = {
    open: 'success',
    limited: 'warning',
    full: 'destructive',
    closed: 'secondary',
};

/** The calendar event class per state, matching site/css/site/sections.css. */
export const SLOT_STATE_CLASSES = {
    open: 'is-available',
    limited: 'is-limited',
    full: 'is-full',
    closed: 'is-closed',
};

/** "2 of 5 booked" — the phrase the whole slot UI is built around. */
export function slotOccupancy(slot) {
    if (!slot) return '';

    return `${slot.reserved ?? 0} / ${slot.capacity ?? 0}`;
}

/** 0 = Sunday, matching Carbon dayOfWeek and the bulk generator's mask. */
export const WEEKDAYS = [
    { value: 0, label: 'Sun' },
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
];
