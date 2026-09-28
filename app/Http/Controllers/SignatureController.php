<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $binary = base64_decode(substr($request->input('signature'), strlen('data:image/png;base64,')), true);
        if ($binary === false || ! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return back()->withErrors(['signature' => 'Please draw your signature before saving.']);
        }

        $user = Auth::user();

        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $path = 'signatures/' . $user->id . '-' . Str::random(20) . '.png';
        Storage::disk('public')->put($path, $binary);
        $user->update(['signature_path' => $path]);

        return back()->with('success', 'Your signature has been saved.');
    }
}
