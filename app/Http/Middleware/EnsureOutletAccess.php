<?php

namespace App\Http\Middleware;

use App\Models\Outlet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards routes that are model-bound to a specific {outlet}.
 * Register as 'outlet.access' and attach to any route/resource where the
 * URL identifies a single outlet (show/edit/update/destroy).
 *
 * Routes without a bound {outlet} (e.g. index/create/store) pass straight
 * through untouched — filter those inside the controller instead, using
 * Outlet::accessibleBy(auth()->user()) or $user->accessibleOutletIds().
 */
class EnsureOutletAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $outlet = $request->route('outlet');

        if ($outlet instanceof Outlet) {
            $user = auth()->user();

            if (!$user || !$user->canAccessOutlet($outlet->id)) {
                abort(403, 'You do not have access to this outlet.');
            }
        }

        return $next($request);
    }
}
