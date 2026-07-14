<?php

return [
    'title' => 'Users',
    'singular' => 'User',
    'add' => 'Add user',
    'edit' => 'Edit user',
    'search' => 'Search users…',

    'fields' => [
        'firstname' => 'First name',
        'lastname' => 'Last name',
        'name' => 'Name',
        'username' => 'Username',
        'email' => 'Email',
        'phone' => 'Phone',
        'password' => 'Password',
        'avatar' => 'Avatar',
        'status' => 'Status',
        'roles' => 'Roles',
        'created_at' => 'Created',
    ],

    'status' => [
        'verified' => 'Approved',
        'pending' => 'Pending',
    ],

    'actions' => [
        'roles' => 'Manage roles',
        'verify' => 'Approve',
        'unverify' => 'Revoke approval',
    ],

    'roles_drawer' => [
        'title' => 'Roles for :name',
        'assign' => 'Assign roles',
        'current' => 'Assigned roles',
        'empty' => 'No roles assigned yet.',
    ],
];
