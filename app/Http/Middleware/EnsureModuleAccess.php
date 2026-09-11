<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * Gate an entire module's routes behind the user's module_user grant
     * (admins bypass everything via Gate::before).
     */
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        abort_unless(
            $request->user()->hasRole('admin') || $request->user()->modules()->where('slug', $moduleSlug)->exists(),
            403,
        );

        return $next($request);
    }
}
