<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
}
