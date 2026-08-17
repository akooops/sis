/**
 * Form status — mirrors App\States\Form\FormStatus. Only `published` is served,
 * and only a published form accepts submissions.
 *
 * `scheduled` is a promise the server keeps: forms:publish-scheduled flips it
 * once published_at passes.
 */
export const FORM_STATUS_LABELS = {
    draft: 'Draft',
    scheduled: 'Scheduled',
    published: 'Published',
    hidden: 'Hidden',
};

/** Badge variant per status — only `published` is a success. */
export const FORM_STATUS_VARIANTS = {
    draft: 'secondary',
    scheduled: 'warning',
    published: 'success',
    hidden: 'destructive',
};

/**
 * Mirrors FormStatus::config(). The 422 stays the enforcement — this only stops
 * the form offering a door the server has locked.
 *
 * Draft has no route to Hidden (a draft was never public) and Published has none
 * to Draft (pulling a live form is an explicit take-down, via Hidden).
 */
export const FORM_STATUS_TRANSITIONS = {
    draft: ['scheduled', 'published'],
    scheduled: ['draft', 'published', 'hidden'],
    published: ['hidden', 'scheduled'],
    hidden: ['draft', 'scheduled', 'published'],
};

/** Statuses reachable from `current`, including staying put. Create has no current. */
export function reachableStatuses(current = null) {
    if (!current) return ['draft', 'scheduled', 'published'];

    return [current, ...(FORM_STATUS_TRANSITIONS[current] ?? [])];
}

/**
 * Only a schedule needs a date from the user. Publishing is always "now" — the
 * server stamps published_at — so asking for it would be a field with one
 * correct answer.
 */
export function needsPublishedAt(status) {
    return status === 'scheduled';
}

/** How a per-visitor cap decides who "the same visitor" is. */
export const LIMIT_BY_OPTIONS = [
    { value: 'ip', label: 'IP address' },
    { value: 'fingerprint', label: 'Device signature' },
    { value: 'both', label: 'Either one' },
];

export const CONFIRMATION_TYPE_OPTIONS = [
    { value: 'message', label: 'Show a message' },
    { value: 'redirect', label: 'Redirect to a URL' },
];

/** Submission outcomes — mirrors FormSubmission::STATUSES. */
export const SUBMISSION_STATUS_LABELS = {
    started: 'In progress',
    completed: 'Completed',
    abandoned: 'Abandoned',
    spam: 'Spam',
    validation_failed: 'Failed validation',
};

export const SUBMISSION_STATUS_VARIANTS = {
    started: 'secondary',
    completed: 'success',
    abandoned: 'warning',
    spam: 'destructive',
    validation_failed: 'destructive',
};

/**
 * Device buckets the submissions filter offers.
 *
 * A plain list rather than a resource: the column is a 16-char string written
 * by the telemetry beacon, not a table, so there is nothing to page through.
 */
export const DEVICE_TYPE_OPTIONS = [
    { value: 'desktop', label: 'Desktop' },
    { value: 'mobile', label: 'Mobile' },
    { value: 'tablet', label: 'Tablet' },
];

/** The element code whose answer holds media ids rather than a readable value. */
export const FILE_FIELD_TYPE = 'file';

/**
 * The element code whose answer is a LIST OF OBJECTS rather than a scalar or a
 * list of scalars — a repeatable group. Mirrors GroupType::code().
 *
 * The only answer shape formatAnswer() deliberately refuses to flatten: an
 * education history rendered as `[object Object], [object Object]` is worse than
 * useless, so the drawer renders each entry itself from the snapshot's `children`.
 */
export const GROUP_FIELD_TYPE = 'group';

/**
 * One group answer as a list of {key, label, value} rows per entry.
 *
 * Reads the labels off the SNAPSHOT taken at submit time, not the live form, for
 * the same reason the rest of the drawer does: renaming a child today must not
 * silently retitle every submission collected before it.
 */
export function groupEntries(value, snapshotChildren = {}) {
    if (!Array.isArray(value)) return [];

    const order = Object.keys(snapshotChildren).sort(
        (a, b) => (snapshotChildren[a]?.order ?? Infinity) - (snapshotChildren[b]?.order ?? Infinity),
    );

    return value.filter((entry) => entry && typeof entry === 'object').map((entry) => ({
        // Snapshot order first, then any key the entry holds that the snapshot
        // does not name — same rule the answer list itself follows.
        rows: [...order, ...Object.keys(entry).filter((key) => !(key in snapshotChildren))].map((key) => ({
            key,
            type: snapshotChildren[key]?.type ?? null,
            value: entry[key] ?? null,
        })),
    }));
}

/**
 * One stored answer as a display string.
 *
 * The client-side counterpart of FieldType::display(), and deliberately the
 * dumber of the two: the server renders for a CSV an admin keeps, this renders
 * for a cell they glance at. Anything richer (a file's name, an option's
 * translated label) is the caller's job — it has the snapshot and the media.
 */
export function formatAnswer(value) {
    if (value === null || value === undefined || value === '') return '';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';

    if (Array.isArray(value)) {
        return value
            .map((item) => formatAnswer(item))
            .filter((item) => item !== '')
            .join(', ');
    }

    if (typeof value === 'object') return JSON.stringify(value);

    return String(value);
}

/** How full a capped form is, 0–100, or null when it has no cap. */
export function capacityPercent(form) {
    if (!form?.is_limited || !form?.submissions_limit) return null;

    return Math.min(100, Math.round((form.submissions_count / form.submissions_limit) * 100));
}
