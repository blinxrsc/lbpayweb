<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('role:admin') or ->middleware('role:admin|outlet_manager')
     *
     * NOTE: the previous version of this middleware checked the role but
     * called $next($request) unconditionally in every branch, so it never
     * actually blocked anyone. Fixed to abort(403) when the user doesn't
     * hold one of the given roles.
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized.');
        }

        $roles = explode('|', $roles);

        if (!auth()->user()->hasAnyRole($roles)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
