<?php

return [
    'title' => 'Roles',
    'singular' => 'Role',
    'add' => 'Add role',
    'edit' => 'Edit role',
    'search' => 'Search roles…',

    'fields' => [
        'name' => 'Name',
        'is_default' => 'Default',
        'permissions' => 'Permissions',
        'created_at' => 'Created',
    ],

    'actions' => [
        'permissions' => 'Manage permissions',
    ],

    'permissions_drawer' => [
        'title' => 'Permissions for :name',
        'assign' => 'Assign permissions',
        'current' => 'Assigned permissions',
        'empty' => 'No permissions assigned yet.',
    ],
];
