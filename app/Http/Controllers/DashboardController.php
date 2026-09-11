<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The module launcher: every account lands here after login and
     * picks a module, even when only one is available, for consistency.
     */
    public function index(): View
    {
        $user = Auth::user();

        $modules = $user->hasRole('admin')
            ? Module::where('is_active', true)->get()
            : $user->modules()->where('is_active', true)->get();

        return view('dashboard', compact('modules'));
    }

    /**
     * Land the user on the right home screen inside a module.
     */
    public function enter(Module $module): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user->hasRole('admin') || $user->modules()->whereKey($module->id)->exists(), 403);

        return match ($module->slug) {
            'palestra' => $user->hasRole('member') ? redirect()->route('member.area') : redirect()->route('courses.index'),
            default => redirect()->route('dashboard'),
        };
    }
}
