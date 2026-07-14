<?php

/**
 * Shared admin UI strings used by the component kit (tables, filters, actions,
 * empty states, etc.). Feature-specific strings live in their own files
 * (e.g. lang/en/admin/users.php) and are exposed to Svelte as `t('users.*')`.
 */
return [

    'actions' => [
        'create' => 'Create',
        'add' => 'Add',
        'edit' => 'Edit',
        'view' => 'View',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'apply' => 'Apply',
        'reset' => 'Reset',
        'close' => 'Close',
        'confirm' => 'Confirm',
        'search' => 'Search',
        'filter' => 'Filter',
        'export' => 'Export',
        'download' => 'Download',
        'copy' => 'Copy',
        'expand' => 'Expand',
        'collapse' => 'Collapse',
        'refresh' => 'Refresh',
        'dark_mode' => 'Dark Mode',
        'dark_sidebar' => 'Dark Sidebar',
        'logout' => 'Log out',
    ],

    'nav' => [
        'dashboard' => 'Dashboard',
        'access_control' => 'Access Control',
        'users' => 'Users',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'api_keys' => 'API Keys',
        'content' => 'Content',
        'media' => 'Media Library',
    ],

    'table' => [
        'actions' => 'Actions',
        'no_results_title' => 'No results found',
        'no_results_body' => 'No records match your criteria.',
        'empty_title' => 'Nothing here yet',
        'empty_body' => 'No records have been created yet.',
        'loading' => 'Loading…',
        'per_page' => 'per page',
        'showing' => 'Showing :from–:to of :total',
    ],

    'filters' => [
        'title' => 'Filters',
        'all' => 'All',
        'yes' => 'Yes',
        'no' => 'No',
        'from' => 'From',
        'to' => 'To',
        'clear' => 'Clear filters',
        'trashed' => 'Deleted records',
        'include_deleted' => 'Include deleted',
        'only_deleted' => 'Only deleted',
    ],

    'media' => [
        'library' => 'Media library',
        'upload' => 'Upload',
        'choose' => 'Choose file',
        'drop_here' => 'Drag & drop a file here, or click to browse',
        'max_size' => 'Maximum size: :size',
        'allowed' => 'Allowed: :types',
        'scanning' => 'Scanning…',
        'select' => 'Select',
        'remove' => 'Remove',
    ],

    'detail' => [
        'id' => 'ID',
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'show_codes' => 'Show Barcode & QR Code',
        'hide_codes' => 'Hide Barcode & QR Code',
        'barcode' => 'Barcode',
        'qr_code' => 'QR Code',
        'print' => 'Print',
    ],

    'confirm' => [
        'delete_title' => 'Delete confirmation',
        'delete_body' => 'Are you sure you want to delete this record? This action cannot be undone.',
    ],

    'feedback' => [
        'created' => 'Created successfully.',
        'updated' => 'Updated successfully.',
        'deleted' => 'Deleted successfully.',
        'error' => 'Something went wrong. Please try again.',
        'network_error' => 'Network error. Please try again.',
    ],
];
