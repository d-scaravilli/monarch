<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit');
    }

    /**
     * Saved immediately on tap (see settings/edit.blade.php), no separate
     * submit button. Returns JSON so the page can update without a reload.
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,auto',
        ]);

        Auth::user()->update($validated);

        return response()->json(['theme' => $validated['theme']]);
    }
}
