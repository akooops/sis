/**
 * Token the admin drops into the email body; ShipNewsletter swaps it for that
 * recipient's own link at send time.
 *
 * MIRRORS Newsletter::UNSUBSCRIBE_PLACEHOLDER — change one, change the other, or
 * the button inserts a token the job no longer replaces.
 */
export const UNSUBSCRIBE_PLACEHOLDER = '{{unsubscribe_url}}';

/**
 * Token swapped for the recipient's own address at send time.
 *
 * MIRRORS Newsletter::EMAIL_PLACEHOLDER — change one, change the other.
 */
export const EMAIL_PLACEHOLDER = '{{email}}';

/* ---------- Email send pipeline: NEWSLETTER_STATUS_*, reachableStatuses, needsScheduledAt ---------- */

/**
 * Newsletter status — mirrors App\States\Newsletter\NewsletterStatus.
 *
 * Only draft and scheduled are ever settable: sending/sent/failed are written by
 * newsletters:send-scheduled and ShipNewsletter, so they are display-only here.
 */
export const NEWSLETTER_STATUS_LABELS = {
    draft: 'Draft',
    scheduled: 'Scheduled',
    sending: 'Sending',
    sent: 'Sent',
    failed: 'Failed',
};

/** Badge variant per status — `sent` is the only success, `sending` is in flight. */
export const NEWSLETTER_STATUS_VARIANTS = {
    draft: 'secondary',
    scheduled: 'warning',
    sending: 'info',
    sent: 'success',
    failed: 'destructive',
};

/** The only two the form may offer — the rest belong to the command and the job. */
const FORM_STATUSES = ['draft', 'scheduled'];

/**
 * Mirrors NewsletterStatus::config(). The 422 stays the enforcement — this only
 * stops the form offering a door the server has locked.
 *
 * Sent is terminal and Sending is mid-flight, so neither leads anywhere here.
 */
export const NEWSLETTER_STATUS_TRANSITIONS = {
    draft: ['scheduled'],
    scheduled: ['draft'],
    sending: [],
    sent: [],
    failed: ['draft', 'scheduled'],
};

/**
 * Statuses reachable from `current`, including staying put. Create has no current.
 * Filtered to FORM_STATUSES so a failed newsletter is offered a way out but never
 * a way back into failed.
 */
export function reachableStatuses(current = null) {
    if (!current) return [...FORM_STATUSES];

    return [current, ...(NEWSLETTER_STATUS_TRANSITIONS[current] ?? [])].filter((s) => FORM_STATUSES.includes(s));
}

/**
 * Only a schedule needs a date from the user. There is no send-now endpoint, so
 * "send now" is a schedule for now — the date is always the user's to give.
 */
export function needsScheduledAt(status) {
    return status === 'scheduled';
}

/* ---------- Website publish pipeline: NEWSLETTER_PUBLISH_STATUS_*, publishReachableStatuses, needsPublishedAt ---------- */

/**
 * Publish status — mirrors App\States\NewsletterPublication\NewsletterPublicationStatus.
 *
 * The website archive alone, and nothing above it: an issue can be live on the
 * site and unsent, or sent and unlisted. Never read one set for the other.
 */
export const NEWSLETTER_PUBLISH_STATUS_LABELS = {
    draft: 'Draft',
    scheduled: 'Scheduled',
    published: 'Published',
    hidden: 'Hidden',
};

/** Badge variant per publish status — only `published` is a success. */
export const NEWSLETTER_PUBLISH_STATUS_VARIANTS = {
    draft: 'secondary',
    scheduled: 'warning',
    published: 'success',
    hidden: 'destructive',
};

/**
 * Mirrors NewsletterPublicationStatus::config(). The 422 stays the enforcement —
 * this only stops the form offering a door the server has locked.
 *
 * Draft has no route to Hidden (a draft was never public) and Published none to
 * Draft (pulling a live issue is an explicit take-down, via Hidden).
 */
export const NEWSLETTER_PUBLISH_STATUS_TRANSITIONS = {
    draft: ['scheduled', 'published'],
    scheduled: ['draft', 'published', 'hidden'],
    published: ['hidden', 'scheduled'],
    hidden: ['draft', 'scheduled', 'published'],
};

/** Publish statuses reachable from `current`, including staying put. Create has no current. */
export function publishReachableStatuses(current = null) {
    if (!current) return ['draft', 'scheduled', 'published'];

    return [current, ...(NEWSLETTER_PUBLISH_STATUS_TRANSITIONS[current] ?? [])];
}

/**
 * Only a schedule needs a date from the user. Publishing is always "now" — the
 * server stamps published_at — so asking for it would be a field with one
 * correct answer.
 */
export function needsPublishedAt(status) {
    return status === 'scheduled';
}
