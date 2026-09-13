@php
    $user = auth()->user();
    $isAdmin = $user->hasRole('admin');
    $isInstructor = $user->hasRole('instructor');
    $isMember = $user->hasRole('member');
    $accentColor = $currentModule->color ?? 'gray';
    $accentIcon = $currentModule->icon ?? 'squares-2x2';
    $accentImage = $currentModule?->imageUrl();
    $accent = \App\Support\ModuleTheme::classes($accentColor);
    $hasMultipleModules = $accessibleModules->count() > 1;

    // The nav shows only the current context's items: just "Impostazioni"
    // on the launcher, or only the active module's own pages once inside one.
    $navItems = [];

    if (! $currentModule) {
        $navItems[] = ['label' => 'Impostazioni', 'route' => 'settings.edit', 'icon' => 'cog-6-tooth', 'active' => request()->routeIs('settings.*'), 'mobile' => true];
    } elseif ($currentModule->slug === 'palestra' && ($isMember || $isInstructor)) {
        // Instructor gets exactly the same pages as member — no Dashboard —
        // plus Team (filtered to their own courses, see MemberController).
        // Extra abilities inside these pages (notes, description, presence
        // management) come from CoursePolicy::manageAttendance, not from
        // seeing a different set of pages.
        $navItems[] = ['label' => 'La mia area', 'route' => 'member.area', 'icon' => 'user-circle', 'active' => request()->routeIs('member.area'), 'mobile' => true];
        $navItems[] = ['label' => 'Corsi ed eventi', 'route' => 'courses.index', 'icon' => 'academic-cap', 'active' => request()->routeIs('courses.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Lezioni', 'route' => 'lessons.index', 'icon' => 'calendar-days', 'active' => request()->routeIs('lessons.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Calendario', 'route' => 'palestra.calendar', 'icon' => 'calendar', 'active' => request()->routeIs('palestra.calendar'), 'mobile' => true];
        if ($isInstructor) {
            $navItems[] = ['label' => 'Team', 'route' => 'members.team', 'icon' => 'user-group', 'active' => request()->routeIs('members.*'), 'mobile' => true];
            $navItems[] = ['label' => 'Progressi', 'route' => 'progress.index', 'icon' => 'chart-bar', 'active' => request()->routeIs('progress.*'), 'mobile' => false];
        }
    } elseif ($currentModule->slug === 'palestra' && $isAdmin) {
        $navItems[] = ['label' => 'Dashboard', 'route' => 'palestra.dashboard', 'icon' => 'home', 'active' => request()->routeIs('palestra.dashboard'), 'mobile' => true];
        $navItems[] = ['label' => 'Corsi ed eventi', 'route' => 'courses.index', 'icon' => 'academic-cap', 'active' => request()->routeIs('courses.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Lezioni', 'route' => 'lessons.index', 'icon' => 'calendar-days', 'active' => request()->routeIs('lessons.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Team', 'route' => 'members.team', 'icon' => 'user-group', 'active' => request()->routeIs('members.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Progressi', 'route' => 'progress.index', 'icon' => 'chart-bar', 'active' => request()->routeIs('progress.*'), 'mobile' => false];
        $navItems[] = ['label' => 'Calendario', 'route' => 'palestra.calendar', 'icon' => 'calendar', 'active' => request()->routeIs('palestra.calendar'), 'mobile' => true];
        $navItems[] = ['label' => 'Contabilità', 'route' => 'accounting.index', 'icon' => 'banknotes', 'active' => request()->routeIs('accounting.*'), 'mobile' => false];
        $navItems[] = ['label' => 'Gestione modulo', 'route' => 'modules.settings.edit', 'params' => [$currentModule], 'icon' => 'wrench-screwdriver', 'active' => request()->routeIs('modules.settings.*'), 'mobile' => false];
    } elseif ($currentModule->slug === 'amministrazione') {
        $navItems[] = ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home', 'active' => request()->routeIs('admin.dashboard'), 'mobile' => true];
        $navItems[] = ['label' => 'Utenti', 'route' => 'admin.users.index', 'icon' => 'users', 'active' => request()->routeIs('admin.users.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Permessi', 'route' => 'admin.permissions.index', 'icon' => 'shield-check', 'active' => request()->routeIs('admin.permissions.*'), 'mobile' => true];
    }
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

        <title>{{ isset($header) ? $header.' - ' : '' }}{{ config('app.name', 'Monarch') }}</title>

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
        @livewireStyles
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50 dark:bg-gray-950 dark:text-gray-100">
        <div class="lg:flex lg:items-start">
            {{-- Desktop / tablet sidebar --}}
            <aside class="hidden lg:flex lg:w-64 lg:shrink-0 lg:flex-col lg:h-screen lg:sticky lg:top-0 border-r border-gray-100 dark:border-white/10 px-5 py-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 mb-8">
                    <x-module-badge :icon="$accentIcon" :color="$accentColor" :image="$accentImage" size="h-9 w-9" />
                    <span class="text-lg font-semibold tracking-tight">{{ $currentModule->name ?? 'Monarch' }}</span>
                </a>

                <nav class="flex-1 space-y-1">
                    @if ($currentModule && $hasMultipleModules)
                        <x-sidebar-link :href="route('dashboard')" icon="arrow-left" :active="false">
                            Torna ai moduli
                        </x-sidebar-link>
                        <div class="!my-3 border-t border-gray-100 dark:border-white/10"></div>
                    @endif

                    @foreach ($navItems as $item)
                        <x-sidebar-link :href="route($item['route'], $item['params'] ?? [])" :icon="$item['icon']" :color="$accentColor" :active="$item['active']">
                            {{ $item['label'] }}
                        </x-sidebar-link>
                    @endforeach
                </nav>

                <div class="mt-auto pt-4 border-t border-gray-100 dark:border-white/10">
                    <div class="flex items-start justify-between gap-2 px-2 mb-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                        </div>
                        <a href="{{ route('settings.edit') }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300">
                            <x-heroicon-o-cog-6-tooth class="h-5 w-5" />
                        </a>
                    </div>
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
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <x-module-badge :icon="$accentIcon" :color="$accentColor" :image="$accentImage" size="h-8 w-8" />
                        <span class="text-lg font-semibold tracking-tight">{{ $header ?? ($currentModule->name ?? 'Monarch') }}</span>
                    </a>
                    <a href="{{ route('settings.edit') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 dark:bg-white/10 text-white text-sm font-semibold overflow-hidden">
                        @if ($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="" class="h-full w-full object-cover">
                        @else
                            {{ Str::of($user->name)->substr(0, 1)->upper() }}
                        @endif
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
            @foreach (collect($navItems)->where('mobile', true) as $item)
                <x-tab-link :href="route($item['route'], $item['params'] ?? [])" :icon="$item['icon']" :color="$accentColor" :active="$item['active']">
                    {{ $item['label'] }}
                </x-tab-link>
            @endforeach
        </nav>

        @livewireScripts
    </body>
</html>
