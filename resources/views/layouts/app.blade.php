@php
    $user = auth()->user();
    $isStaff = $user->hasAnyRole(['admin', 'instructor']);
    $isMember = $user->hasRole('member');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f9fafb">

        <title>{{ isset($header) ? $header.' - ' : '' }}{{ config('app.name', 'Beru') }}</title>

        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <link rel="icon" href="/icons/icon-192.png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50">
        <div class="lg:flex lg:items-start">
            {{-- Desktop / tablet sidebar --}}
            <aside class="hidden lg:flex lg:w-64 lg:shrink-0 lg:flex-col lg:h-screen lg:sticky lg:top-0 border-r border-gray-100 px-5 py-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 mb-8">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gray-900 text-white font-semibold">B</span>
                    <span class="text-lg font-semibold tracking-tight">Beru</span>
                </a>

                <nav class="flex-1 space-y-1">
                    @if ($isStaff)
                        <x-sidebar-link :href="route('editions.index')" icon="users" :active="request()->routeIs('editions.*')">
                            Edizioni
                        </x-sidebar-link>
                    @endif

                    @if ($isMember)
                        <x-sidebar-link :href="route('member.area')" icon="user-circle" :active="request()->routeIs('member.area')">
                            La mia area
                        </x-sidebar-link>
                    @endif

                    <x-sidebar-link :href="route('profile.edit')" icon="identification" :active="request()->routeIs('profile.*')">
                        Profilo
                    </x-sidebar-link>
                </nav>

                <div class="mt-auto pt-4 border-t border-gray-100">
                    <p class="px-2 text-sm font-medium text-gray-900 truncate">{{ $user->name }}</p>
                    <p class="px-2 text-xs text-gray-400 truncate mb-3">{{ $user->email }}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100">
                            <x-heroicon-o-arrow-right-start-on-rectangle class="h-5 w-5" />
                            Esci
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex-1 min-h-screen flex flex-col">
                {{-- Mobile top bar --}}
                <header class="lg:hidden sticky top-0 z-20 flex items-center justify-between bg-gray-50/90 backdrop-blur px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-3">
                    <span class="text-lg font-semibold tracking-tight">{{ $header ?? 'Beru' }}</span>
                    <a href="{{ route('profile.edit') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 text-white text-sm font-semibold">
                        {{ Str::of($user->name)->substr(0, 1)->upper() }}
                    </a>
                </header>

                @if (session('status'))
                    <div class="mx-4 lg:mx-10 mt-4">
                        <div class="rounded-2xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700 ring-1 ring-green-100">
                            {{ session('status') }}
                        </div>
                    </div>
                @endif

                @isset($header)
                    <div class="hidden lg:block px-10 pt-8">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ $header }}</h1>
                    </div>
                @endisset

                <main class="flex-1 px-4 py-5 sm:px-6 lg:px-10 lg:py-8 pb-24 lg:pb-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Mobile bottom tab bar --}}
        <nav class="lg:hidden fixed inset-x-0 bottom-0 z-20 flex border-t border-gray-100 bg-white/95 backdrop-blur pb-[env(safe-area-inset-bottom)]">
            @if ($isStaff)
                <x-tab-link :href="route('editions.index')" icon="users" :active="request()->routeIs('editions.*')">
                    Edizioni
                </x-tab-link>
            @endif

            @if ($isMember)
                <x-tab-link :href="route('member.area')" icon="user-circle" :active="request()->routeIs('member.area')">
                    La mia area
                </x-tab-link>
            @endif

            <x-tab-link :href="route('profile.edit')" icon="identification" :active="request()->routeIs('profile.*')">
                Profilo
            </x-tab-link>
        </nav>
    </body>
</html>
