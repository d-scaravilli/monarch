<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The module launcher. An account with access to more than one module
     * picks here; an account with exactly one module skips straight into
     * it — there's nothing to choose, so the extra click would be pure
     * friction (this is the common case for an instructor/member, who
     * only ever have Palestra).
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        $modules = $user->hasRole('admin')
            ? Module::where('is_active', true)->get()
            : $user->modules()->where('is_active', true)->get();

        if ($modules->count() === 1) {
            return $this->enter($modules->first());
        }

        $moduleAlerts = $this->resolveModuleAlerts($user, $modules);

        return view('dashboard', compact('modules', 'moduleAlerts'));
    }

    /**
     * Small operational-warning counts shown as a badge on each module
     * card. Palestra's is expiring medical certificates; a future
     * module adds its own case here.
     *
     * @param  Collection<int, Module>  $modules
     * @return array<int, int>
     */
    private function resolveModuleAlerts(User $user, Collection $modules): array
    {
        $alerts = [];

        foreach ($modules as $module) {
            $alerts[$module->id] = match ($module->slug) {
                'palestra' => $user->hasRole('admin')
                    ? Document::where('type', 'certificato_medico')->whereBetween('expiry_date', [today(), today()->addDays(30)])->count()
                    : 0,
                default => 0,
            };
        }

        return $alerts;
    }

    /**
     * Land the user on the right home screen inside a module.
     */
    public function enter(Module $module): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user->hasRole('admin') || $user->modules()->whereKey($module->id)->exists(), 403);

        return match ($module->slug) {
            'palestra' => $user->hasRole('member') ? redirect()->route('member.area') : redirect()->route('palestra.dashboard'),
            'amministrazione' => redirect()->route('admin.dashboard'),
            default => redirect()->route('dashboard'),
        };
    }
}
