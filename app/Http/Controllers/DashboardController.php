<?php

namespace App\Http\Controllers;

use App\Models\MedicalCertificate;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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
                    ? MedicalCertificate::whereBetween('expiry_date', [today(), today()->addDays(30)])->count()
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
            default => redirect()->route('dashboard'),
        };
    }
}
