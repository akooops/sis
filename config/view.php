<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    */

    /*
     * Two roots, because resources/ is split into admin/ and site/ — the admin
     * SPA shell and the public site are separate apps with separate bundles.
     * Both are also registered as the `admin::` and `site::` namespaces in
     * AppServiceProvider; prefer those prefixes in our own code, so a name that
     * exists on both sides can never resolve to the wrong half.
     */
    'paths' => [
        resource_path('admin/views'),
        resource_path('site/views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
