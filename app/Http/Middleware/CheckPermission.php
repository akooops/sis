<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if(config('app.enable_permissions')){
            if (!$request->user() || !$request->user()->hasPermission($permission)) {
                abort(403, 'You do not have the necessary permissions to access this resource.');
            }
        }

        return $next($request);
    }
}