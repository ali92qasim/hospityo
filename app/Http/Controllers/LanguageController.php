<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch the application language
     */
    public function switch(Request $request, $locale)
    {
        // Validate locale
        $availableLocales = ['en', 'fr', 'es', 'de', 'ar'];
        
        if (!in_array($locale, $availableLocales)) {
            return redirect()->back()->with('error', 'Invalid language selected');
        }

        // Store locale in session
        Session::put('locale', $locale);

        // If user is authenticated, update their locale preference
        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        return redirect()->back()->with('success', 'Language changed successfully');
    }
}
