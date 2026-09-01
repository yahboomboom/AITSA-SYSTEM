<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
// This controller handles the editing and updating of user signatures. It allows users to upload their signature images, which are stored in the public disk and associated with their user profile.
class SignatureController extends Controller
{ // Display the signature edit form for the authenticated user
    public function edit()
    {
        return view('signature.edit', ['user' => Auth::user()]);
    }
    // Handle the signature update request, validating and storing the uploaded signature image
    public function update(Request $request)
    { // Validate the uploaded signature image, ensuring it meets the required criteria (image type and size)
        $request->validate([
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
        ]);

        $user = Auth::user();
    // If the user already has a signature, delete the old signature file from storage to free up space and avoid clutter
        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }
    // Store the new signature image in the 'signatures' directory on the public disk and update the user's signature path in the database
        $path = $request->file('signature')->store('signatures', 'public');
        $user->update(['signature_path' => $path]);
    // Redirect back to the signature edit page with a success message indicating that the signature has been saved successfully
        return back()->with('success', 'Your signature has been saved.');
    }
}