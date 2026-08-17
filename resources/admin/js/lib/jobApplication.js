/**
 * Job application status — mirrors App\States\JobApplication\JobApplicationStatus.
 *
 * `received`, not `new`: `new` is a reserved word in PHP and cannot be a class
 * name, so the stored value matches the class rather than the nicer word.
 */
export const JOB_APPLICATION_STATUS_LABELS = {
    received: 'Received',
    shortlisted: 'Shortlisted',
    contacted: 'Contacted',
    called: 'Called',
    hired: 'Hired',
    rejected: 'Rejected',
};

/** Badge variant per status — `hired` is the only success, `rejected` the only failure. */
export const JOB_APPLICATION_STATUS_VARIANTS = {
    received: 'secondary',
    shortlisted: 'primary',
    contacted: 'info',
    called: 'warning',
    hired: 'success',
    rejected: 'destructive',
};

/**
 * Mirrors JobApplicationStatus::config(). The 422 stays the enforcement — this
 * only stops the menu offering a door the server has locked.
 *
 * Forward, plus reject from anywhere: a candidate can drop out at any point and
 * HR should never have to walk one through three stages to close it.
 * `rejected -> shortlisted` is the one way back, because people are rejected by
 * mistake and a reopen leaves an audit row where deleting and re-entering would
 * leave nothing. `hired` is EMPTY on purpose — undoing a hire is a decision with
 * paperwork attached, not a dropdown.
 */
export const JOB_APPLICATION_TRANSITIONS = {
    received: ['shortlisted', 'rejected'],
    shortlisted: ['contacted', 'rejected'],
    contacted: ['called', 'hired', 'rejected'],
    called: ['hired', 'rejected'],
    hired: [],
    rejected: ['shortlisted'],
};

/**
 * How to reach each state: the endpoint, the wording, and the permission.
 *
 * One permission PER TRANSITION rather than a blanket `update`, because a school
 * wants a screener who can shortlist but not reject, and a manager who can hire
 * without touching the queue.
 */
export const TRANSITION_ACTIONS = {
    shortlisted: {
        action: 'shortlist',
        label: 'Shortlist',
        icon: 'ki-check-circle',
        permission: 'job-applications.shortlist',
    },
    contacted: {
        action: 'contact',
        label: 'Mark contacted',
        icon: 'ki-sms',
        permission: 'job-applications.contact',
    },
    called: {
        action: 'call',
        label: 'Mark called',
        icon: 'ki-phone',
        permission: 'job-applications.call',
    },
    hired: {
        action: 'hire',
        label: 'Mark hired',
        icon: 'ki-medal-star',
        permission: 'job-applications.hire',
    },
    rejected: {
        action: 'reject',
        label: 'Reject',
        icon: 'ki-cross-circle',
        permission: 'job-applications.reject',
        variant: 'destructive',
    },
};

/**
 * The moves available from `status`, in the order the ladder runs.
 *
 * An unknown status yields nothing rather than throwing: the row still renders,
 * it just offers no transitions, which is the safe way to fail.
 */
export function availableTransitions(status) {
    return (JOB_APPLICATION_TRANSITIONS[status] ?? []).map((target) => TRANSITION_ACTIONS[target]).filter(Boolean);
}
