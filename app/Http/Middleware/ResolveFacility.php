<?php

namespace App\Http\Middleware;

use App\Models\Facility;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ResolveFacility
{
    /**
     * Resolve the current facility from either the wildcard subdomain
     * ({facilityDomain}) or the path prefix ({facilitySlug}), share it with
     * every view and default the route parameter so route() calls inside the
     * mini-site don't have to pass it explicitly.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        $domain = $route->parameter('facilityDomain');
        $slug = $route->parameter('facilitySlug');

        if ($domain !== null) {
            $facility = Facility::where('status', 'published')->where('domain', $domain)->first();
        } else {
            $facility = Facility::where('status', 'published')->where('slug', $slug)->first();
        }

        if (! $facility) {
            abort(404);
        }

        // Keep controller signatures clean: the facility travels on the
        // request, not as a route argument.
        $route->forgetParameter($domain !== null ? 'facilityDomain' : 'facilitySlug');

        $request->attributes->set('facility', $facility);
        $request->attributes->set('facilityRoutePrefix', $domain !== null ? 'facility.domain.' : 'facility.');

        app()->instance('currentFacility', $facility);

        URL::defaults($domain !== null
            ? ['facilityDomain' => $facility->domain]
            : ['facilitySlug' => $facility->slug]);

        view()->share('facility', $facility);

        return $next($request);
    }
}
