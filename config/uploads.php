<?php

return [

    /*
     * Files are written to `quarantine_disk` first and only moved to their
     * target disk once App\Jobs\ScanUpload clears them. `quarantine_disk` must
     * not be web-readable.
     */

    'quarantine_disk' => env('FILE_QUARANTINE_DISK', 'quarantine'),

    /*
     * The default target. Kept as a top-level key because it predates the
     * registry below and UploadService still falls back to it.
     */
    'disk' => env('MEDIA_DISK', 'public'),
    'folder' => env('FILE_DEFAULT_FOLDER', 'uploads'),

    /*
     * The target registry. A target is `public` or `private:<feature>`, and
     * UploadService resolves it to a disk plus a folder which it records on the
     * media row (media.disk / media.folder) — nothing downstream re-derives it.
     *
     * `public` is the world-readable disk the admin UI links to. `private` is
     * not web-readable: its files are only ever served through a signed,
     * permission-gated route, which is what lets a public form collect a CV
     * without publishing it.
     *
     * Storage stays FLAT WITHIN A FOLDER — the owner and collection remain a
     * database concern, so attach/detach are still pure DB writes, and file
     * names never collide because UploadService generates a ULID filename.
     * A folder is shared by every file in that feature, so the standing rule
     * holds: delete a media's FILE, never its directory.
     *
     * A new feature that needs private storage adds one line under `folders`.
     */
    'disks' => [
        'public' => [
            'disk' => env('MEDIA_DISK', 'public'),
            'folder' => env('FILE_DEFAULT_FOLDER', 'uploads'),
        ],
        'private' => [
            'disk' => env('PRIVATE_MEDIA_DISK', 'local'),
            'folders' => [
                'forms' => 'forms', 
            ],
        ],
    ],

    /*
     * The maximum file size of an upload, in bytes.
     */
    'max_file_size' => (int) env('FILE_MAX_SIZE', 1024 * 1024 * 10), // 10MB

    /*
     * There is no general orphan sweep. An unattached upload stays in the
     * library until someone deletes it from the Media page — see
     * MediaController::destroy and the note on App\Models\Media. Editor-inserted
     * images are free media by design, so a blanket sweep would eventually
     * delete files that live pages still render.
     *
     * The one carve-out is private form uploads: they are never inserted into
     * any page's HTML, so an abandoned one is pure waste. See the `prunable`
     * predicate on App\Models\Media, which keys off disk+folder (indexed
     * columns) and never touches the public disk.
     */
    'prune_private_after_days' => (int) env('FILE_PRUNE_PRIVATE_AFTER_DAYS', 7),

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
