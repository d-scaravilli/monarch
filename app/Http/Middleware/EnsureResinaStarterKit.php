<?php

namespace App\Http\Middleware;

use App\Services\Resina\StarterKit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResinaStarterKit
{
    public function __construct(private StarterKit $starterKit) {}

    /**
     * Every "3D - Resina" page can be the first one a user opens (a
     * shared link, a bookmark), so the starter kit is checked here
     * rather than on the home page alone.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->starterKit->ensureFor($request->user());

        return $next($request);
    }
}
