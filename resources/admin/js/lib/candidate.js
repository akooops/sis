/**
 * Reading a candidate, and above all reading a MATCH SCORE.
 *
 * `score ?? 0` IS FORBIDDEN ANYWHERE IN THIS APP. A null score means the scoring
 * queue has not reached this pair yet; 0 means the model looked and judged them a
 * poor fit. Rendering the first as the second tells an admin that someone was
 * assessed and failed when nobody has assessed them at all — which is the exact
 * mistake CandidateMatch::scopeStrong's `whereNotNull` exists to prevent on the
 * server. Use isScored() to branch, never a truthiness check on the number.
 *
 * No AI provider is configured today, so every score in the system is null and
 * every one of these paths is the one being exercised.
 */

/** Mirrors CandidateMatch::SHORTLIST_THRESHOLD. The API also sends it per row. */
export const SHORTLIST_THRESHOLD = 60;

export const NOT_SCORED = 'Not scored yet';
export const NO_SUMMARY = 'No summary yet';
export const NO_CV = 'No CV on file';

/** Whether a score exists at all. `0` is a score; null and undefined are not. */
export function isScored(score) {
    return score !== null && score !== undefined;
}

export function scoreLabel(score) {
    return isScored(score) ? String(score) : NOT_SCORED;
}

/**
 * Badge variant for a score.
 *
 * `secondary` for an unscored one — NEVER `destructive`. A red badge is a verdict,
 * and there is no verdict yet.
 */
export function scoreVariant(score) {
    if (!isScored(score)) return 'secondary';
    if (score >= SHORTLIST_THRESHOLD) return 'success';
    if (score >= 40) return 'warning';

    return 'destructive';
}

/** Mirrors Candidate::PROFICIENCIES. Null is "not stated", not "none". */
export const PROFICIENCY_LABELS = {
    basic: 'Basic',
    intermediate: 'Intermediate',
    advanced: 'Advanced',
    native: 'Native',
};

export function proficiencyLabel(proficiency) {
    return PROFICIENCY_LABELS[proficiency] ?? 'Not stated';
}

/**
 * A year range for an education or experience row.
 *
 * `is_current` is carried separately from a null end year because "still there"
 * and "did not say when they left" are different answers — the first reads
 * "Present", the second trails off.
 */
export function yearRange(start, end, isCurrent = false) {
    const to = isCurrent ? 'Present' : (end ?? null);

    if (!start && !to) return '';
    if (!start) return String(to);
    if (!to) return `${start} –`;

    return `${start} – ${to}`;
}
