<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn ($user) => $user->hasRole('admin') ? true : null);

        // A soft-deleted member/course's history must stay reachable by
        // the admin, even though they vanish from the active list/queries.
        Route::bind('member', fn ($value) => User::withTrashed()->findOrFail($value));
        Route::bind('course', fn ($value) => Course::withTrashed()->findOrFail($value));

        // The header icon and accent color shift to match whichever
        // module's routes are currently active. Shared with every view
        // (not just the layout) since a Blade component's slot content
        // renders in the caller's scope, not the layout's.
        View::composer('*', function ($view) {
            $view->with('currentModule', $this->resolveCurrentModule());
            $view->with('accessibleModules', $this->resolveAccessibleModules());
            $view->with('appIconVersion', AppSetting::current()->icon_version);
        });
    }

    /**
     * Every active module the current user can enter — all of them for
     * admin, only the granted ones otherwise. Drives the single/multiple
     * module navigation (skip the launcher, hide "Torna ai moduli", list
     * shortcuts in Impostazioni) from one shared source.
     */
    private function resolveAccessibleModules(): Collection
    {
        if (! auth()->check()) {
            return collect();
        }

        $user = auth()->user();

        return $user->hasRole('admin')
            ? Module::where('is_active', true)->get()
            : $user->modules()->where('is_active', true)->get();
    }

    /**
     * Every module's routes sit behind Route::middleware('module:<slug>'),
     * so the slug is read straight from there — instead of matching
     * route-name prefixes against a hand-maintained list, which silently
     * drifts out of sync every time a page is added under a route name
     * the list doesn't already know about (as happened with progress.*).
     * This way a new page just needs the same module: middleware it
     * already needs for access control, and it's automatically covered.
     */
    private function resolveCurrentModule(): ?Module
    {
        $route = request()->route();

        // Routes that already bind a {module} parameter (e.g. the generic
        // module-settings page) know their module directly — no need to
        // inspect middleware for those.
        $boundModule = $route?->parameter('module');
        if ($boundModule instanceof Module) {
            return $boundModule;
        }

        foreach ($route?->gatherMiddleware() ?? [] as $middleware) {
            if (str_starts_with($middleware, 'module:')) {
                return Module::where('slug', substr($middleware, strlen('module:')))->first();
            }
        }

        return null;
    }
}
