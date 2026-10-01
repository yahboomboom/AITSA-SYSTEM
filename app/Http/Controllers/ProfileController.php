<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const HOME_ROUTES = [
        'student' => 'dashboard',
        'chair' => 'approver.dashboard',
        'cashier' => 'cashier.dashboard',
        'registrar' => 'registrar.dashboard',
        'admission' => 'registrar.dashboard',
        'department_officer' => 'department.dashboard',
        'admin' => 'admin.dashboard',
        'faculty' => 'faculty.schedule',
    ];

    public function edit()
    {
        $user = Auth::user();
        $homeUrl = route(self::HOME_ROUTES[$user->role] ?? 'dashboard');

        return view('profile.edit', compact('user', 'homeUrl'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($data);

        return back()->with('success', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        AuditLog::record('Password Changed', $user->name . ' changed their own password.', 'User', $user->id);

        return back()->with('success', 'Your password has been changed.');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $user = Auth::user();

        // Students reset through the Registrar (identity checked in person),
        // never by email — same rule as the login page's Forgot password.
        if ($user->role === 'student') {
            $user->forceFill(['password_reset_requested_at' => now()])->save();
            AuditLog::record('Password Reset Requested', $user->name . ' (' . $user->login_id . ') asked the Registrar for a password reset.', 'User', $user->id);
            $message = 'Request sent to the Registrar. Visit the Registrar\'s office with your ID to get a temporary password.';

            return $request->expectsJson()
                ? response()->json(['viaRegistrar' => true, 'message' => $message])
                : back()->with('success', $message);
        }

        Password::sendResetLink(['email' => $user->email]);
        $message = 'A password reset link has been sent to your email address.';

        return $request->expectsJson()
            ? response()->json(['viaRegistrar' => false, 'message' => $message])
            : back()->with('success', $message);
    }
}
