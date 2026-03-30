<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMinRole
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('min_role:member')
     * Allows the specified role and any role above it in the hierarchy.
     * Hierarchy: visitor (0) < member (1) < admin (2) < super_admin (3)
     */
    public function handle(Request $request, Closure $next, string $minRole): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasMinRole($minRole)) {
            abort(403, 'Your account level does not have permission to access this page.');
        }

        return $next($request);
    }
}
