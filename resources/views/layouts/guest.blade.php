<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark bg-gray-50" data-theme-pref="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#030712">

        <title>{{ config('app.name', 'Monarch') }}</title>

        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <link rel="icon" href="/icons/icon-192.png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased">
        <div class="relative min-h-screen flex flex-col justify-center items-center overflow-hidden px-4 py-12 bg-gray-50 dark:bg-gray-950">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -top-32 -left-24 h-80 w-80 rounded-full bg-orange-200/40 dark:bg-orange-500/10 blur-3xl"></div>
                <div class="absolute -bottom-32 -right-24 h-80 w-80 rounded-full bg-blue-200/40 dark:bg-blue-500/10 blur-3xl"></div>
            </div>

            <div class="relative flex flex-col items-center">
                <a href="/" class="flex items-center gap-2.5 mb-10">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-900 dark:bg-white/10 text-white font-semibold text-xl">M</span>
                    <span class="text-2xl font-semibold tracking-tight">Monarch</span>
                </a>

                <div class="w-full sm:max-w-md rounded-2xl bg-white dark:bg-gray-900 px-8 py-10 shadow-sm ring-1 ring-gray-100 dark:ring-white/10">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
