@php
    $user = auth()->user();
    $isStaff = $user->hasAnyRole(['admin', 'instructor']);
    $isMember = $user->hasRole('member');
    $accentColor = $currentModule->color ?? 'gray';
    $accentIcon = $currentModule->icon ?? 'squares-2x2';
    $accent = \App\Support\ModuleTheme::classes($accentColor);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="bg-gray-50 {{ $user->theme === 'dark' ? 'dark' : '' }}"
      data-theme-pref="{{ $user->theme }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f9fafb">

        <title>{{ isset($header) ? $header.' - ' : '' }}{{ config('app.name', 'Beru') }}</title>

        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <link rel="icon" href="/icons/icon-192.png">

        <script>
            // Runs before the stylesheet paints, so "auto" never flashes the
            // wrong theme. Explicit light/dark is already set server-side above.
            (function () {
                if (document.documentElement.dataset.themePref !== 'auto') return;
                var apply = function () {
                    document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches);
                };
                apply();
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', apply);
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50 dark:bg-gray-950 dark:text-gray-100">
        <div class="lg:flex lg:items-start">
            {{-- Desktop / tablet sidebar --}}
            <aside class="hidden lg:flex lg:w-64 lg:shrink-0 lg:flex-col lg:h-screen lg:sticky lg:top-0 border-r border-gray-100 dark:border-white/10 px-5 py-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 mb-8">
                    <x-module-badge :icon="$accentIcon" :color="$accentColor" size="h-9 w-9" />
                    <span class="text-lg font-semibold tracking-tight">{{ $currentModule->name ?? 'Beru' }}</span>
                </a>

                <nav class="flex-1 space-y-1">
                    <x-sidebar-link :href="route('dashboard')" icon="squares-2x2" :active="request()->routeIs('dashboard')">
                        Moduli
                    </x-sidebar-link>

                    @if ($isStaff)
                        <x-sidebar-link :href="route('courses.index')" icon="academic-cap" :color="$accentColor" :active="request()->routeIs('courses.*')">
                            Corsi
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('members.index')" icon="users" :color="$accentColor" :active="request()->routeIs('members.*')">
                            Iscritti
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('lessons.index')" icon="calendar-days" :color="$accentColor" :active="request()->routeIs('lessons.*')">
                            Lezioni
                        </x-sidebar-link>
                    @endif

                    @if ($isMember)
                        <x-sidebar-link :href="route('member.area')" icon="user-circle" :color="$accentColor" :active="request()->routeIs('member.area')">
                            La mia area
                        </x-sidebar-link>
                    @endif

                    <x-sidebar-link :href="route('profile.edit')" icon="identification" :active="request()->routeIs('profile.*')">
                        Profilo
                    </x-sidebar-link>
                </nav>

                <div class="mt-auto pt-4 border-t border-gray-100 dark:border-white/10">
                    <p class="px-2 text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $user->name }}</p>
                    <p class="px-2 text-xs text-gray-400 truncate mb-3">{{ $user->email }}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                            <x-heroicon-o-arrow-right-start-on-rectangle class="h-5 w-5" />
                            Esci
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex-1 min-h-screen flex flex-col">
                {{-- Mobile top bar --}}
                <header class="lg:hidden sticky top-0 z-20 flex items-center justify-between bg-gray-50/90 dark:bg-gray-950/90 backdrop-blur px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-3">
                    <div class="flex items-center gap-2">
                        <x-module-badge :icon="$accentIcon" :color="$accentColor" size="h-8 w-8" />
                        <span class="text-lg font-semibold tracking-tight">{{ $header ?? ($currentModule->name ?? 'Beru') }}</span>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 dark:bg-white/10 text-white text-sm font-semibold">
                        {{ Str::of($user->name)->substr(0, 1)->upper() }}
                    </a>
                </header>

                @if (session('status'))
                    <div class="mx-4 lg:mx-10 mt-4">
                        <div class="rounded-2xl bg-green-50 dark:bg-green-500/10 px-4 py-3 text-sm font-medium text-green-700 dark:text-green-400 ring-1 ring-green-100 dark:ring-green-500/20">
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
        <nav class="lg:hidden fixed inset-x-0 bottom-0 z-20 flex border-t border-gray-100 dark:border-white/10 bg-white/95 dark:bg-gray-900/95 backdrop-blur pb-[env(safe-area-inset-bottom)]">
            <x-tab-link :href="route('dashboard')" icon="squares-2x2" :active="request()->routeIs('dashboard')">
                Moduli
            </x-tab-link>

            @if ($isStaff)
                <x-tab-link :href="route('courses.index')" icon="academic-cap" :color="$accentColor" :active="request()->routeIs('courses.*')">
                    Corsi
                </x-tab-link>
                <x-tab-link :href="route('members.index')" icon="users" :color="$accentColor" :active="request()->routeIs('members.*')">
                    Iscritti
                </x-tab-link>
                <x-tab-link :href="route('lessons.index')" icon="calendar-days" :color="$accentColor" :active="request()->routeIs('lessons.*')">
                    Lezioni
                </x-tab-link>
            @endif

            @if ($isMember)
                <x-tab-link :href="route('member.area')" icon="user-circle" :color="$accentColor" :active="request()->routeIs('member.area')">
                    La mia area
                </x-tab-link>
            @endif

            <x-tab-link :href="route('profile.edit')" icon="identification" :active="request()->routeIs('profile.*')">
                Profilo
            </x-tab-link>
        </nav>
    </body>
</html>
