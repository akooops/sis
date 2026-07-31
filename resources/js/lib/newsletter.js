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

/* ------------------------------------------------------------------ *
 * WEBSITE side — published_status + published_at. Never the email one.
 * ------------------------------------------------------------------ */

/**
 * Mirrors App\States\NewsletterPublication\NewsletterPublicationStatus.
 *
 * The archive alone: an issue can be live on the site and unsent, or sent and
 * unlisted. published_at is the future date while scheduled and the moment it went
 * live once published.
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

/**
 * Publish statuses reachable from `current`, including staying put. Create has no
 * current, and no issue is born hidden — there is nothing public to withdraw yet.
 */
export function publishReachableStatuses(current = null) {
    if (!current) return ['draft', 'scheduled', 'published'];

    return [current, ...(NEWSLETTER_PUBLISH_STATUS_TRANSITIONS[current] ?? [])];
}

/**
 * Only a schedule needs a date from the user. Publishing is always "now" — the
 * server schedules it for this instant — so asking would be a field with one
 * correct answer.
 */
export function needsPublishAt(status) {
    return status === 'scheduled';
}

/* ------------------------------------------------------------------ *
 * EMAIL side — sent_status + sent_at. Never the website one.
 * ------------------------------------------------------------------ */

/**
 * Mirrors App\States\NewsletterSend\NewsletterSendStatus.
 *
 * The broadcast alone. sent_at is the future date while scheduled and the moment
 * it went out once sent.
 */
export const NEWSLETTER_SEND_STATUS_LABELS = {
    draft: 'Draft',
    scheduled: 'Scheduled',
    sent: 'Sent',
    failed: 'Failed',
};

/** Badge variant per send status — `sent` is the only success. */
export const NEWSLETTER_SEND_STATUS_VARIANTS = {
    draft: 'secondary',
    scheduled: 'warning',
    sent: 'success',
    failed: 'destructive',
};

/**
 * Mirrors NewsletterSendStatus::config(). Sent is NOT terminal: Sent -> Scheduled
 * re-sends the issue to every subscriber, so the form confirms before allowing it.
 * Failed only leads back to Draft or Scheduled — fix the audience, then re-queue.
 */
export const NEWSLETTER_SEND_STATUS_TRANSITIONS = {
    draft: ['scheduled'],
    scheduled: ['draft', 'sent'],
    sent: ['scheduled'],
    failed: ['draft', 'scheduled'],
};

/**
 * Send statuses reachable from `current`, including staying put. Create has no
 * current.
 *
 * `failed` is stripped even when it IS the current status: only
 * newsletters:send-scheduled writes it, the DTO's Rule::in refuses it from a
 * client, and a failed issue needs a way out rather than a way to stay put.
 */
export function sendReachableStatuses(current = null) {
    if (!current) return ['draft', 'scheduled', 'sent'];

    return [current, ...(NEWSLETTER_SEND_STATUS_TRANSITIONS[current] ?? [])].filter((s) => s !== 'failed');
}

/**
 * Only a schedule needs a date from the user. There is no send-now endpoint — all
 * sending goes through the command — so "send now" is a schedule for this instant
 * that the server stamps itself.
 */
export function needsSendAt(status) {
    return status === 'scheduled';
}
