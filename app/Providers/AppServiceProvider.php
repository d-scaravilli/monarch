<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Which route-name prefixes belong to which module. The only entry
     * today is Palestra; a future module adds its own line here.
     */
    private const MODULE_ROUTES = [
        'palestra' => ['palestra.', 'courses.', 'members.', 'lessons.', 'enrollments.', 'payments.', 'member.area', 'rooms.'],
        'amministrazione' => ['admin.'],
    ];

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
        });
    }

    private function resolveCurrentModule(): ?Module
    {
        $route = request()->route();

        // Routes that already bind a {module} parameter (e.g. the generic
        // module-settings page) know their module directly — no need to
        // guess from the route name.
        $boundModule = $route?->parameter('module');
        if ($boundModule instanceof Module) {
            return $boundModule;
        }

        $routeName = optional($route)->getName() ?? '';

        foreach (self::MODULE_ROUTES as $slug => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                    return Module::where('slug', $slug)->first();
                }
            }
        }

        return null;
    }
}
