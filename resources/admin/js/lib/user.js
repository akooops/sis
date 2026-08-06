/**
 * User account status — mirrors App\States\User\UserStatus.
 *
 * Only `approved` may sign in: `verified` means Azure confirmed the identity,
 * not that an admin has let them in.
 */
export const USER_STATUS_LABELS = {
    pending: 'Pending',
    verified: 'Verified',
    approved: 'Approved',
    rejected: 'Rejected',
};

/** Badge variant per status — only `approved` is a success. */
export const USER_STATUS_VARIANTS = {
    pending: 'warning',
    verified: 'primary',
    approved: 'success',
    rejected: 'destructive',
};
