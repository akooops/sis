<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * Null by default, which is the safe end: an untrusted X-Forwarded-For is
     * ignored and request()->ip() is whatever actually connected.
     *
     * This matters beyond logging. Public forms enforce per-IP submission caps
     * and per-form IP/country blocks off request()->ip(). Deployed behind a
     * proxy with this unset, every visitor reads as the proxy — one shared
     * bucket for the caps, and the proxy's country for the block. Trusting a
     * proxy that is NOT in front of the app is the opposite failure: any client
     * could then spoof the header and walk past both.
     *
     * Set TRUSTED_PROXIES at deploy time: '*' when the app is only reachable
     * through a load balancer or CDN you control, otherwise a comma-separated
     * list of proxy addresses.
     */
    public function __construct()
    {
        $proxies = config('app.trusted_proxies');

        $this->proxies = $proxies === '*'
            ? '*'
            : (filled($proxies) ? array_map('trim', explode(',', (string) $proxies)) : null);
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
