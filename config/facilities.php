<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Facilities Root Domain
    |--------------------------------------------------------------------------
    |
    | The base domain used for facility mini-website subdomains. A facility
    | with domain "hive" is served at "hive.<root_domain>". Facilities are
    | always also reachable at "<app-url>/facilities/{slug}".
    |
    */

    'root_domain' => env('FACILITY_ROOT_DOMAIN'),

];
