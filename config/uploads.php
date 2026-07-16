<?php

return [

    /*
     * Files are written to `quarantine_disk` first and only moved to `disk`
     * once App\Jobs\ScanUpload clears them. `quarantine_disk` must not be
     * web-readable; `disk` is the public one the UI links to.
     */
    
    'disk' => env('MEDIA_DISK', 'public'),
    'quarantine_disk' => env('FILE_QUARANTINE_DISK', 'quarantine'),

    /*
     * The single folder every file is stored in, on both disks. There are no
     * per-model or per-collection directories: the owner and the collection are
     * a database concern, which is what lets attach/detach be pure DB writes.
     * Names never collide because App\Services\Uploads\UploadService generates
     * a ULID filename (the original name is kept in media.name for display).
     */
    'folder' => env('FILE_DEFAULT_FOLDER', 'uploads'),

    /*
     * The maximum file size of an upload, in bytes.
     */
    'max_file_size' => (int) env('FILE_MAX_SIZE', 1024 * 1024 * 10), // 10MB

    /*
     * How long an unattached upload survives before uploads:prune deletes it.
     */
    'max_orphaned_files_age' => (int) env('FILE_MAX_ORPHANED_FILES_AGE', 30), // days

    'scanner' => env('FILE_SCANNER', 'null'), // null | clamav

    'clamav' => [
        'socket' => env('CLAMAV_SOCKET'),
        'host' => env('CLAMAV_HOST', '127.0.0.1'),
        'port' => (int) env('CLAMAV_PORT', 3310),
        'timeout' => (int) env('CLAMAV_TIMEOUT', 30),
    ],

    'allowed_types' => [
        'images' => explode(',', env('FILE_ALLOWED_IMAGES', 'jpg,jpeg,png,gif,webp,svg')),
        'documents' => explode(',', env('FILE_ALLOWED_DOCUMENTS', 'pdf,doc,docx,xls,xlsx,csv,ppt,pptx,txt,zip')),
        'videos' => explode(',', env('FILE_ALLOWED_VIDEOS', 'mp4,avi,mov,wmv,flv')),
        'audio' => explode(',', env('FILE_ALLOWED_AUDIO', 'mp3,wav,ogg,m4a')),
    ],
];
