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
     * `code` is the array key (stored on notifications.type and referenced by
     * NotificationService::send()); `name`/`icon` are display; `sort` orders the
     * type pickers.
     */
    'types' => [
        'system.announcement' => ['name' => 'System announcement', 'icon' => 'ki-notification-status', 'sort' => 1],
        'user.approved' => ['name' => 'User approved', 'icon' => 'ki-check-circle', 'sort' => 2],
        'user.rejected' => ['name' => 'User rejected', 'icon' => 'ki-cross-circle', 'sort' => 3],
    ],

];
