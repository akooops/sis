<?php

return [
    'title' => 'Media library',
    'singular' => 'Media',
    'search' => 'Search media…',

    'fields' => [
        'name' => 'Name',
        'type' => 'Type',
        'size' => 'Size',
        'status' => 'Scan',
        'attached' => 'Attached',
        'created_at' => 'Uploaded',
    ],

    'types' => [
        'images' => 'Image',
        'documents' => 'Document',
        'videos' => 'Video',
        'audio' => 'Audio',
    ],

    'tabs' => [
        'all' => 'All',
        'images' => 'Images',
        'audio' => 'Audio',
        'videos' => 'Videos',
        'documents' => 'Documents',
    ],

    'actions' => [
        'detach' => 'Free (detach)',
    ],

    'attached' => [
        'yes' => 'In use',
        'no' => 'Free',
    ],
];
