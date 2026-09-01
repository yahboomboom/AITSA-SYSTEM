<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        $homeRoutes = [
            'student' => 'dashboard',
            'chair' => 'approver.dashboard',
            'cashier' => 'cashier.dashboard',
            'registrar' => 'registrar.dashboard',
            'admission' => 'registrar.dashboard',
            'department_officer' => 'department.dashboard',
            'admin' => 'admin.dashboard',
            'faculty' => 'faculty.schedule',
        ];

        $homeRoute = $homeRoutes[$user->role] ?? 'dashboard';

        return view('signature.edit', ['user' => $user, 'homeUrl' => route($homeRoute)]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
        ]);

        $user = Auth::user();

        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $path = $request->file('signature')->store('signatures', 'public');
        $user->update(['signature_path' => $path]);

        return back()->with('success', 'Your signature has been saved.');
    }
}
