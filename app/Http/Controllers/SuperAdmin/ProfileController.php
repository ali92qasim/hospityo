<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::guard('super_admin')->user();
        return view('super-admin.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::guard('super_admin')->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:super_admins,email,' . $user->id,
        ]);

        try {
            $user->update($validated);
            return back()->with('success', 'Profile updated successfully.');
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Profile update failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update profile.');
        }
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::guard('super_admin')->user();

        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $user->update(['password' => Hash::make($request->password)]);
            return back()->with('success', 'Password updated successfully.');
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Password update failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update password.');
        }
    }
}
