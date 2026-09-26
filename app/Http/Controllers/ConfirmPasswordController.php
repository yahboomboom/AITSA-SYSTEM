<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ConfirmPasswordController extends Controller
{
    private const SIDEBAR_BY_ROLE = [
        'cashier' => ['partial' => 'partials.cashier-sidebar', 'roleLabel' => 'Cashier Staff'],
        'registrar' => ['partial' => 'partials.registrar-sidebar', 'roleLabel' => 'Registrar Portal'],
        'admission' => ['partial' => 'partials.registrar-sidebar', 'roleLabel' => 'Registrar Portal'],
        'admin' => ['partial' => 'partials.admin-sidebar', 'roleLabel' => 'Administrator'],
    ];

    public function show(): View
    {
        $role = Auth::user()->role;
        $sidebarPartial = self::SIDEBAR_BY_ROLE[$role]['partial'] ?? null;
        $roleLabel = self::SIDEBAR_BY_ROLE[$role]['roleLabel'] ?? 'Staff';

        return view('auth.confirm-password', compact('sidebarPartial', 'roleLabel'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            AuditLog::record(
                'Password Re-confirmation Failed',
                Auth::user()->name . ' entered the wrong password while re-confirming access to a sensitive page.',
                'User',
                Auth::id()
            );

            return back()->withErrors(['password' => 'That password is incorrect.']);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('login'));
    }
}
