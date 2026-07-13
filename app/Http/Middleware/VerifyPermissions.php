<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPermissions
{
    /**
     * Ensure the authenticated user or API key holds the given permission code
     * (e.g. "users.index"). Gated by config('app.enable_permissions').
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! config('app.enable_permissions')) {
            return $next($request);
        }

        // Web user -> abort so the exception handler renders an error page.
        if ($user = $request->user()) {
            abort_unless($user->hasPermission($permission), 403, 'Access forbidden.');

            return $next($request);
        }

        // API key -> JSON error response.
        $apiKey = $request->attributes->get('apiKey');

        if ($apiKey && ! $apiKey->hasPermission($permission)) {
            return $this->forbidden();
        }

        return $next($request);
    }

    protected function forbidden(): Response
    {
        return response()->json([
            'status' => 'error',
            'code' => 403,
            'message' => 'Access forbidden.',
            'details' => 'You do not have the necessary permissions to access this resource.',
        ], 403);
    }
}
