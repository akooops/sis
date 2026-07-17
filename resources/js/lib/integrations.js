/**
 * Shared bits for the Integrations UI — mirrors lib/user.js. One source for the
 * type-card status pill.
 */

/** Type-card status → pill label. */
export const STATUS_LABELS = {
    active: 'Active',
    disabled: 'Disabled',
    not_configured: 'Not configured',
};

/** Type-card status → Badge variant. */
export const STATUS_VARIANTS = {
    active: 'success',
    disabled: 'secondary',
    not_configured: 'secondary',
};
