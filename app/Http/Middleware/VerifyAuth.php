<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAuth
{
    /**
     * Authenticate the request via a single API key (X-API-KEY: {prefix}-{secret})
     * or a Sanctum session/token. The resolved ApiKey is stashed on the request
     * for VerifyPermissions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-API-KEY');

        if ($token) {
            $apiKey = ApiKey::validate($token, $request->ip());

            if (! $apiKey) {
                return $this->unauthorized();
            }

            $request->attributes->set('apiKey', $apiKey);

            return $next($request);
        }

        if (auth('sanctum')->check()) {
            return $next($request);
        }

        return $this->unauthorized();
    }

    protected function unauthorized(): Response
    {
        return response()->json([
            'status' => 'error',
            'code' => 401,
            'message' => 'Authentication required.',
            'details' => 'You must provide valid credentials to access this resource.',
        ], 401);
    }
}
