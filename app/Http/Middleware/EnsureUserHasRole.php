<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Abort with 403 unless the authenticated user's role is one of the given roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = Auth::user()?->role;

        if (! $userRole || ! in_array($userRole, $roles, true)) {
            abort(403, 'You are not authorized to access this section.');
        }

        return $next($request);
    }
}
