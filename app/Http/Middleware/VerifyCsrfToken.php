<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Analytics beacons only. navigator.sendBeacon cannot set headers, so it
        // can never carry the token. Nothing here writes an answer: the endpoint
        // authenticates the submission through its own encrypted token, and the
        // form POST itself stays CSRF-protected.
        'forms/*/telemetry',
    ];
}
