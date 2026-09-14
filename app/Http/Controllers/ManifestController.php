<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    /**
     * The PWA manifest, generated on the fly so icon URLs can carry the
     * current icon version as a cache-busting query string — replaces the
     * old static public/manifest.json, which couldn't do that.
     */
    public function __invoke(): JsonResponse
    {
        $version = AppSetting::current()->icon_version;

        return response()->json([
            // Matches the previous static manifest.json exactly — config('app.name')
            // resolves to the unrelated .env APP_NAME ("Laravel"), not this app's
            // own branding, so it can't be used as the source here.
            'name' => 'Monarch',
            'short_name' => 'Monarch',
            'description' => 'Gestionale personale a moduli - Palestra',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#f9fafb',
            'theme_color' => '#f9fafb',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => "/icons/icon-192.png?v={$version}",
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => "/icons/icon-512.png?v={$version}",
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ]);
    }
}
