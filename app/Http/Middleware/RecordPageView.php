<?php

namespace App\Http\Middleware;

use App\Services\Analytics\PageViewRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records one row per public page view, after the response has gone out.
 *
 * WHY terminate() AND NOT handle():
 *   1. The two rules that matter most - status 200 and text/html - do not exist
 *      before $next($request) has run.
 *   2. Under FPM this runs after fastcgi_finish_request(), so the insert costs
 *      the visitor nothing.
 *
 * THIS CLASS KEEPS NO STATE, AND THAT IS LOAD-BEARING. Kernel::terminateMiddleware()
 * calls $this->app->make($name), so the object that ran handle() is NOT the one
 * that runs terminate() - a property set on the way in is gone on the way out.
 * handle() is a bare pass-through and everything is read from $request and
 * $response, which is what makes that harmless. If state ever has to cross,
 * put it on $request->attributes (the same object both times); never a property.
 *
 * Attached to BOTH site route groups in routes/web.php, prefixed and unprefixed.
 * That does not double-count: a request matches exactly one Route object and
 * only that object's middleware runs.
 */
class RecordPageView
{
    public function __construct(protected PageViewRecorder $recorder) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $this->recorder->record($request, $response);
        } catch (Throwable $e) {
            /*
             * The response has already been sent, so this is invisible to the
             * visitor and would otherwise land in laravel.log as a bare stack
             * trace on every page view. A counter that cannot count must never
             * look like an application fault - same reasoning as the integration
             * wrappers elsewhere.
             */
            Log::channel('integrations')->warning('analytics.page-view-failed', [
                'route' => $request->route()?->getName(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
