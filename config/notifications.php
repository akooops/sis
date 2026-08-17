<?php

return [

    /*
     * The SUBSCRIBABLE notification type catalogue — the kinds of notification a
     * group can subscribe to. This array is the source of truth;
     * NotificationTypesSeeder mirrors it into the notification_types table, the
     * group form picks from it, and the UI renders from it. Grown by code, never
     * CRUD: add a row here, reseed.
     *
     * A group routes a type to its members: emitting a notification of type <code>
     * through NotificationService::send() fans out to the users of every group
     * whose types include <code>.
     *
     * `code` is the array key — what emitters pass to NotificationService::send()
     * (notifications reference the seeded row by FK); `name`/`icon` are display;
     * `sort` orders the type pickers.
     *
     * EVERY type here is subscribable — there is no second, hidden flavour and
     * nothing is withheld from the group form. A caller may ALSO hand send() a
     * list of group ids (a form hands it the groups attached to that form); the
     * recipients are the union of those and the type's subscribers.
     */
    'types' => [
        'user.pending_approval' => ['name' => 'User pending approval', 'icon' => 'ki-time', 'sort' => 1],
        'form.submission_received' => ['name' => 'Form submission received', 'icon' => 'ki-questionnaire-tablet', 'sort' => 2],

        /*
         * An application form ALSO fires form.submission_received, because it is
         * a form. These two carry the JOB context — which posting, which
         * applicant, where it has reached — so HR subscribes to these and whoever
         * watches forms in general is not drowned in applications.
         */
        'job.application_received' => ['name' => 'Job application received', 'icon' => 'ki-briefcase', 'sort' => 3],
        'job.application_status_changed' => ['name' => 'Job application status changed', 'icon' => 'ki-arrow-right', 'sort' => 4],
    ],

    /*
     * Notifications are append-only with no delete UI; the daily model:prune
     * drops rows (and, via FK cascade, their notification_users) older than this.
     */
    'prune_after_days' => (int) env('NOTIFICATIONS_PRUNE_AFTER_DAYS', 90),
];
