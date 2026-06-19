<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $doctor = null;

        if ($user->hasRole('Doctor')) {
            $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
        }

        return view('profile.edit', compact('user', 'doctor'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->safe()->only(['name', 'email']));

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        // Update doctor-specific fields if user is a Doctor
        if ($request->user()->hasRole('Doctor')) {
            $doctor = \App\Models\Doctor::where('user_id', $request->user()->id)->first();
            if ($doctor) {
                try {
                    $doctorData = $request->safe()->only([
                        'phone', 'specialization', 'qualification', 'pmdc_number',
                        'experience_years', 'consultation_fee', 'shift_start',
                        'shift_end', 'address',
                    ]);
                    // Handle available_days separately (checkbox array)
                    $doctorData['available_days'] = $request->input('available_days', []);
                    // Sync name/email to doctor record
                    $doctorData['name'] = $request->user()->name;
                    $doctorData['email'] = $request->user()->email;

                    $doctor->update($doctorData);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('[Profile] Failed to update doctor data', ['error' => $e->getMessage()]);
                }
            }
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
