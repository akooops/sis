<?php

return [
    'title' => 'API keys',
    'singular' => 'API key',
    'add' => 'Create API key',
    'edit' => 'Edit API key',
    'search' => 'Search API keys…',

    'fields' => [
        'name' => 'Name',
        'prefix' => 'Prefix',
        'allowed_ips' => 'Allowed IPs',
        'allowed_ips_hint' => 'One IP per line. Leave blank to allow all.',
        'expires_at' => 'Expires at',
        'status' => 'Status',
        'last_used_at' => 'Last used',
        'created_at' => 'Created',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'actions' => [
        'permissions' => 'Manage permissions',
        'rotate' => 'Rotate token',
        'revoke' => 'Revoke',
    ],

    'token' => [
        'title' => 'Copy your API token',
        'warning' => 'This token is shown only once. Store it securely now.',
        'copy' => 'Copy',
        'copied' => 'Copied',
    ],

    'permissions_drawer' => [
        'title' => 'Permissions for :name',
        'assign' => 'Assign permissions',
        'current' => 'Assigned permissions',
        'empty' => 'No permissions assigned yet.',
    ],
];
