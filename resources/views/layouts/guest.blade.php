<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f9fafb">

        <title>{{ config('app.name', 'Beru') }}</title>

        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <link rel="icon" href="/icons/icon-192.png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10 bg-gray-50">
            <a href="/" class="flex items-center gap-2 mb-8">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gray-900 text-white font-semibold text-lg">B</span>
                <span class="text-xl font-semibold tracking-tight">Beru</span>
            </a>

            <div class="w-full sm:max-w-md rounded-2xl bg-white px-6 py-8 shadow-sm ring-1 ring-gray-100">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
