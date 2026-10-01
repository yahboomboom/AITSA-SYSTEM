<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A student the Registrar withdraws while logged in keeps a live session
 * until it expires; login only blocks new sessions. End it on their next
 * request instead.
 */
class EndWithdrawnSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user()?->role === 'withdrawn') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login_id' => "Your admission was withdrawn. Please contact the Registrar's office.",
            ]);
        }

        return $next($request);
    }
}
