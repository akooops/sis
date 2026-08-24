/**
 * Where a venue booking has reached at the desk. Mirrors
 * App\States\FacilityReservation\FacilityReservationStatus.
 */
export const FACILITY_RESERVATION_STATUS_LABELS = {
    pending: 'Pending',
    contacted: 'Contacted',
    confirmed: 'Confirmed',
    attended: 'Attended',
    no_show: 'No-show',
    cancelled: 'Cancelled',
};

export const FACILITY_RESERVATION_STATUS_VARIANTS = {
    pending: 'secondary',
    contacted: 'info',
    confirmed: 'primary',
    attended: 'success',
    no_show: 'warning',
    cancelled: 'destructive',
};

/**
 * Mirrors FacilityReservationStatus::config().
 *
 * CANCELLED IS THE ONLY ONE THAT FREES A SEAT, which is why it is reachable from
 * every live state and why reopening it is a move of its own. `attended` is
 * terminal; `no_show` keeps one way back because a register marked before the last
 * family walks in is the mistake this undoes.
 */
export const FACILITY_RESERVATION_TRANSITIONS = {
    pending: ['contacted', 'confirmed', 'cancelled'],
    contacted: ['confirmed', 'cancelled'],
    confirmed: ['attended', 'no_show', 'cancelled'],
    attended: [],
    no_show: ['attended'],
    cancelled: ['pending'],
};

/**
 * One action per TARGET status, each with its own permission — a school wants a
 * receptionist who can confirm without being able to cancel, and somebody marking
 * the register on the day who does neither.
 */
export const TRANSITION_ACTIONS = {
    contacted: { action: 'contact', label: 'Mark contacted', icon: 'ki-sms', permission: 'facility-reservations.contact' },
    confirmed: { action: 'confirm', label: 'Confirm', icon: 'ki-check-circle', permission: 'facility-reservations.confirm' },
    attended: { action: 'attend', label: 'Mark attended', icon: 'ki-verify', permission: 'facility-reservations.attend' },
    no_show: { action: 'no-show', label: 'Mark no-show', icon: 'ki-user-cross', permission: 'facility-reservations.no-show', variant: 'destructive' },
    cancelled: { action: 'cancel', label: 'Cancel', icon: 'ki-cross-circle', permission: 'facility-reservations.cancel', variant: 'destructive' },
    pending: { action: 'reopen', label: 'Reopen', icon: 'ki-arrows-circle', permission: 'facility-reservations.reopen' },
};

/** The moves legal from `status`. An unknown status yields [] rather than throwing. */
export function availableTransitions(status) {
    return (FACILITY_RESERVATION_TRANSITIONS[status] ?? []).map((target) => TRANSITION_ACTIONS[target]).filter(Boolean);
}
