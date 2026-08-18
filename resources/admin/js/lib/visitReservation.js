/**
 * Where a booking has reached at the admissions desk. Mirrors
 * App\States\VisitReservation\VisitReservationStatus.
 */
export const VISIT_RESERVATION_STATUS_LABELS = {
    pending: 'Pending',
    contacted: 'Contacted',
    confirmed: 'Confirmed',
    attended: 'Attended',
    no_show: 'No-show',
    cancelled: 'Cancelled',
};

export const VISIT_RESERVATION_STATUS_VARIANTS = {
    pending: 'secondary',
    contacted: 'info',
    confirmed: 'primary',
    attended: 'success',
    no_show: 'warning',
    cancelled: 'destructive',
};

/**
 * Mirrors VisitReservationStatus::config().
 *
 * CANCELLED IS THE ONLY ONE THAT FREES A SEAT, which is why it is reachable from
 * every live state and why reopening it is a move of its own. `attended` is
 * terminal; `no_show` keeps one way back because a register marked before the last
 * family walks in is the mistake this undoes.
 */
export const VISIT_RESERVATION_TRANSITIONS = {
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
    contacted: { action: 'contact', label: 'Mark contacted', icon: 'ki-sms', permission: 'visit-reservations.contact' },
    confirmed: { action: 'confirm', label: 'Confirm', icon: 'ki-check-circle', permission: 'visit-reservations.confirm' },
    attended: { action: 'attend', label: 'Mark attended', icon: 'ki-verify', permission: 'visit-reservations.attend' },
    no_show: { action: 'no-show', label: 'Mark no-show', icon: 'ki-user-cross', permission: 'visit-reservations.no-show', variant: 'destructive' },
    cancelled: { action: 'cancel', label: 'Cancel', icon: 'ki-cross-circle', permission: 'visit-reservations.cancel', variant: 'destructive' },
    pending: { action: 'reopen', label: 'Reopen', icon: 'ki-arrows-circle', permission: 'visit-reservations.reopen' },
};

/** The moves legal from `status`. An unknown status yields [] rather than throwing. */
export function availableTransitions(status) {
    return (VISIT_RESERVATION_TRANSITIONS[status] ?? []).map((target) => TRANSITION_ACTIONS[target]).filter(Boolean);
}

/**
 * The 15 grades the booking form offers, mirroring FormsSeeder::gradeOptions().
 *
 * The stored answer is the VALUE, never the translated label, so a family who
 * booked in Arabic and one who booked in English are the same row here.
 */
export const GRADE_LABELS = {
    prek: 'Pre-K',
    kg1: 'KG1',
    kg2: 'KG2',
    g1: 'Grade 1',
    g2: 'Grade 2',
    g3: 'Grade 3',
    g4: 'Grade 4',
    g5: 'Grade 5',
    g6: 'Grade 6',
    g7: 'Grade 7',
    g8: 'Grade 8',
    g9: 'Grade 9',
    g10: 'Grade 10',
    g11: 'Grade 11',
    g12: 'Grade 12',
};

/** An unrecognised value is shown as itself: it is still what the family answered. */
export function gradeLabel(value) {
    if (!value) return '';

    return GRADE_LABELS[value] ?? value;
}
