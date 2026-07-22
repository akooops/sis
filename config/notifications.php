<?php

return [

    /*
     * The notification type catalogue — the kinds of notification the app can
     * emit. This array is the source of truth; NotificationTypesSeeder mirrors it
     * into the notification_types table so groups can subscribe to types and the
     * UI can render them. Grown by code, never CRUD: add a row here, reseed.
     *
     * A group routes a type to its members: emitting a notification of type <code>
     * fans out to the users of every group whose types include <code>.
     *
     * `code` is the array key — what observers pass to NotificationService::send()
     * (notifications reference the seeded row by FK); `name`/`icon` are display;
     * `sort` orders the type pickers.
     */
    'types' => [
        'user.pending_approval' => ['name' => 'User pending approval', 'icon' => 'ki-time', 'sort' => 1],
    ],

    /*
     * Notifications are append-only with no delete UI; the daily model:prune
     * drops rows (and, via FK cascade, their notification_users) older than this.
     */
    'prune_after_days' => (int) env('NOTIFICATIONS_PRUNE_AFTER_DAYS', 90),

];
