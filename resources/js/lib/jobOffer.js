/**
 * Job offer status — mirrors App\States\JobOffer\JobOfferStatus. Only `published` is served.
 *
 * `scheduled` is a promise the server keeps: job-offers:publish-scheduled flips it
 * once published_at passes.
 */
export const JOB_OFFER_STATUS_LABELS = {
    draft: 'Draft',
    scheduled: 'Scheduled',
    published: 'Published',
    hidden: 'Hidden',
};

/** Badge variant per status — only `published` is a success. */
export const JOB_OFFER_STATUS_VARIANTS = {
    draft: 'secondary',
    scheduled: 'warning',
    published: 'success',
    hidden: 'destructive',
};

/**
 * Mirrors JobOfferStatus::config(). The 422 stays the enforcement — this only stops
 * the form offering a door the server has locked.
 *
 * Draft has no route to Hidden (a draft was never public) and Published has none
 * to Draft (pulling a live offer is an explicit take-down, via Hidden).
 */
export const JOB_OFFER_STATUS_TRANSITIONS = {
    draft: ['scheduled', 'published'],
    scheduled: ['draft', 'published', 'hidden'],
    published: ['hidden', 'scheduled'],
    hidden: ['draft', 'scheduled', 'published'],
};

/** Statuses reachable from `current`, including staying put. Create has no current. */
export function reachableStatuses(current = null) {
    if (!current) return ['draft', 'scheduled', 'published'];

    return [current, ...(JOB_OFFER_STATUS_TRANSITIONS[current] ?? [])];
}

/**
 * Only a schedule needs a date from the user. Publishing is always "now" — the
 * server stamps published_at — so asking for it would be a field with one
 * correct answer.
 */
export function needsPublishedAt(status) {
    return status === 'scheduled';
}

/** Mirrors JobOffer::EMPLOYMENT_TYPES — schema.org values, lowercased. */
export const EMPLOYMENT_TYPE_LABELS = {
    full_time: 'Full time',
    part_time: 'Part time',
    contractor: 'Contractor',
    temporary: 'Temporary',
    intern: 'Intern',
    volunteer: 'Volunteer',
    per_diem: 'Per diem',
    other: 'Other',
};

/** Mirrors JobOffer::WORK_MODES. */
export const WORK_MODE_LABELS = {
    onsite: 'On site',
    hybrid: 'Hybrid',
    remote: 'Remote',
};

/** Mirrors JobOffer::EDUCATION_LEVELS. */
export const EDUCATION_LEVEL_LABELS = {
    high_school: 'High school',
    associate: 'Associate degree',
    bachelor: "Bachelor's degree",
    professional_certificate: 'Professional certificate',
    postgraduate: 'Postgraduate',
};

/** Null is "unspecified", 0 is entry level — not the same answer. */
export function experienceLabel(years) {
    if (years === null || years === undefined || years === '') return 'Unspecified';
    if (Number(years) === 0) return 'Entry level';

    return `${years} year${Number(years) === 1 ? '' : 's'}`;
}

/**
 * Applications are closed. Derived, never stored: status is editorial and the
 * deadline is operational, so a published offer can sit past its own deadline.
 */
export function deadlinePassed(deadline) {
    return !!deadline && new Date(deadline) < new Date();
}
