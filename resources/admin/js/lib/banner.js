/**
 * Banner status — mirrors App\States\Banner\BannerStatus. Only `published` is served.
 *
 * `scheduled` is a promise the server keeps: banners:publish-scheduled flips it
 * once published_at passes.
 */
export const BANNER_STATUS_LABELS = {
    draft: 'Draft',
    scheduled: 'Scheduled',
    published: 'Published',
    hidden: 'Hidden',
};

/** Badge variant per status — only `published` is a success. */
export const BANNER_STATUS_VARIANTS = {
    draft: 'secondary',
    scheduled: 'warning',
    published: 'success',
    hidden: 'destructive',
};

/**
 * Mirrors BannerStatus::config(). The 422 stays the enforcement — this only stops
 * the form offering a door the server has locked.
 *
 * Draft has no route to Hidden (a draft was never public) and Published has none
 * to Draft (pulling a live banner is an explicit take-down, via Hidden).
 */
export const BANNER_STATUS_TRANSITIONS = {
    draft: ['scheduled', 'published'],
    scheduled: ['draft', 'published', 'hidden'],
    published: ['hidden', 'scheduled'],
    hidden: ['draft', 'scheduled', 'published'],
};

/** Statuses reachable from `current`, including staying put. Create has no current. */
export function reachableStatuses(current = null) {
    if (!current) return ['draft', 'scheduled', 'published'];

    return [current, ...(BANNER_STATUS_TRANSITIONS[current] ?? [])];
}

/**
 * Only a schedule needs a date from the user. Publishing is always "now" — the
 * server stamps published_at — so asking for it would be a field with one
 * correct answer.
 */
export function needsPublishedAt(status) {
    return status === 'scheduled';
}
