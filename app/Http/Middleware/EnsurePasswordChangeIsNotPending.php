<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChangeIsNotPending
{
    private const EXEMPT_ROUTES = ['password.force-change', 'password.force-change.submit', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->must_change_password && ! in_array($request->route()?->getName(), self::EXEMPT_ROUTES, true)) {
            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
