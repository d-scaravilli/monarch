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
        $navItems[] = ['label' => 'Corsi/Eventi', 'route' => 'courses.index', 'icon' => 'academic-cap', 'active' => request()->routeIs('courses.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Lezioni', 'route' => 'lessons.index', 'icon' => 'calendar-days', 'active' => request()->routeIs('lessons.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Messaggi', 'route' => 'messages.index', 'icon' => 'envelope', 'active' => request()->routeIs('messages.*'), 'mobile' => true, 'badge' => $unreadMessagesCount];
        $navItems[] = ['label' => 'Calendario', 'route' => 'palestra.calendar', 'icon' => 'calendar', 'active' => request()->routeIs('palestra.calendar'), 'mobile' => true];
        if ($isInstructor) {
            $navItems[] = ['label' => 'Team', 'route' => 'members.team', 'icon' => 'user-group', 'active' => request()->routeIs('members.*'), 'mobile' => true];
            // The mobile tab bar now scrolls horizontally (see the bottom
            // nav below), so every page can live there instead of being
            // hidden — no "more" menu needed.
            $navItems[] = ['label' => 'Progressi', 'route' => 'progress.index', 'icon' => 'chart-bar', 'active' => request()->routeIs('progress.*'), 'mobile' => true];
        }
    } elseif ($currentModule->slug === 'palestra' && $isAdmin) {
        $navItems[] = ['label' => 'Dashboard', 'route' => 'palestra.dashboard', 'icon' => 'home', 'active' => request()->routeIs('palestra.dashboard'), 'mobile' => true];
        $navItems[] = ['label' => 'Corsi/Eventi', 'route' => 'courses.index', 'icon' => 'academic-cap', 'active' => request()->routeIs('courses.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Lezioni', 'route' => 'lessons.index', 'icon' => 'calendar-days', 'active' => request()->routeIs('lessons.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Team', 'route' => 'members.team', 'icon' => 'user-group', 'active' => request()->routeIs('members.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Messaggi', 'route' => 'messages.index', 'icon' => 'envelope', 'active' => request()->routeIs('messages.*'), 'mobile' => true, 'badge' => $unreadMessagesCount];
        $navItems[] = ['label' => 'Progressi', 'route' => 'progress.index', 'icon' => 'chart-bar', 'active' => request()->routeIs('progress.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Calendario', 'route' => 'palestra.calendar', 'icon' => 'calendar', 'active' => request()->routeIs('palestra.calendar'), 'mobile' => true];
        // The mobile tab bar now scrolls horizontally (see the bottom nav
        // below), so every admin page can live there instead of being
        // hidden — no "more" menu needed.
        $navItems[] = ['label' => 'Contabilità', 'route' => 'accounting.index', 'icon' => 'banknotes', 'active' => request()->routeIs('accounting.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Gestisci', 'route' => 'modules.settings.edit', 'params' => [$currentModule], 'icon' => 'wrench-screwdriver', 'active' => request()->routeIs('modules.settings.*'), 'mobile' => true];
    } elseif ($currentModule->slug === 'resina') {
        // Same pages for everyone; only the admin also edits the shared
        // catalog (inside those pages) and gets "Gestisci". Too many pages
        // for the phone tab bar: the four used most stay there, the rest
        // (mobile => false) go in its "Altro" sheet. "section" groups the
        // sidebar and the sheet.
        $navItems[] = ['label' => 'Home', 'route' => 'resina.home', 'icon' => 'home', 'active' => request()->routeIs('resina.home'), 'mobile' => true, 'section' => 'Dipingere'];
        $navItems[] = ['label' => 'Progetti', 'route' => 'resina.projects.index', 'icon' => 'rectangle-stack', 'active' => request()->routeIs('resina.projects.*', 'resina.characters.*', 'resina.versions.*', 'resina.armor-types.*', 'resina.guides.*'), 'mobile' => true, 'section' => 'Dipingere'];
        $navItems[] = ['label' => 'Le mie figure', 'route' => 'resina.figures.index', 'icon' => 'user-circle', 'active' => request()->routeIs('resina.figures.*'), 'mobile' => false, 'section' => 'Dipingere'];
        $navItems[] = ['label' => 'Analizza foto', 'route' => 'resina.photo.create', 'icon' => 'camera', 'active' => request()->routeIs('resina.photo.*'), 'mobile' => false, 'section' => 'Dipingere'];
        $navItems[] = ['label' => 'Ricette', 'route' => 'resina.recipes.index', 'icon' => 'book-open', 'active' => request()->routeIs('resina.recipes.*'), 'mobile' => true, 'section' => 'Dipingere'];
        $navItems[] = ['label' => 'Trova colore', 'route' => 'resina.finder', 'icon' => 'eye-dropper', 'active' => request()->routeIs('resina.finder'), 'mobile' => false, 'section' => 'Dipingere'];
        $navItems[] = ['label' => 'Mixer', 'route' => 'resina.mixer', 'icon' => 'beaker', 'active' => request()->routeIs('resina.mixer'), 'mobile' => true, 'section' => 'Dipingere'];
        $navItems[] = ['label' => 'I miei colori', 'route' => 'resina.paints.index', 'icon' => 'swatch', 'active' => request()->routeIs('resina.paints.*'), 'mobile' => false, 'section' => 'Il tuo kit'];
        $navItems[] = ['label' => 'I miei pennelli', 'route' => 'resina.brushes.index', 'icon' => 'paint-brush', 'active' => request()->routeIs('resina.brushes.*'), 'mobile' => false, 'section' => 'Il tuo kit'];
        $navItems[] = ['label' => 'Da comprare', 'route' => 'resina.shop.index', 'icon' => 'shopping-bag', 'active' => request()->routeIs('resina.shop.*'), 'mobile' => false, 'section' => 'Il tuo kit'];
        $navItems[] = ['label' => 'Percorso', 'route' => 'resina.path.index', 'icon' => 'map', 'active' => request()->routeIs('resina.path.*'), 'mobile' => false, 'section' => 'Imparare'];
        $navItems[] = ['label' => 'Tecniche', 'route' => 'resina.techniques.index', 'icon' => 'light-bulb', 'active' => request()->routeIs('resina.techniques.*'), 'mobile' => false, 'section' => 'Imparare'];
        $navItems[] = ['label' => 'Tutorial', 'route' => 'resina.tutorials.index', 'icon' => 'play-circle', 'active' => request()->routeIs('resina.tutorials.*'), 'mobile' => false, 'section' => 'Imparare'];
        if ($isAdmin) {
            $navItems[] = ['label' => 'Foto di riferimento', 'route' => 'resina.references.index', 'icon' => 'photo', 'active' => request()->routeIs('resina.references.*'), 'mobile' => false, 'section' => 'Modulo'];
            $navItems[] = ['label' => 'Gestisci', 'route' => 'modules.settings.edit', 'params' => [$currentModule], 'icon' => 'wrench-screwdriver', 'active' => request()->routeIs('modules.settings.*'), 'mobile' => false, 'section' => 'Modulo'];
        }
    } elseif ($currentModule->slug === 'amministrazione') {
        $navItems[] = ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home', 'active' => request()->routeIs('admin.dashboard'), 'mobile' => true];
        $navItems[] = ['label' => 'Utenti', 'route' => 'admin.users.index', 'icon' => 'users', 'active' => request()->routeIs('admin.users.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Permessi', 'route' => 'admin.permissions.index', 'icon' => 'shield-check', 'active' => request()->routeIs('admin.permissions.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Aspetto', 'route' => 'admin.appearance.edit', 'icon' => 'photo', 'active' => request()->routeIs('admin.appearance.*'), 'mobile' => true];
        $navItems[] = ['label' => 'Notifiche', 'route' => 'admin.notifications.index', 'icon' => 'bell', 'active' => request()->routeIs('admin.notifications.*'), 'mobile' => true];
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
        <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">

        <title>@isset($header){{ $header }} - @endisset{{ config('app.name', 'Monarch') }}</title>

        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png?v={{ $appIconVersion }}">
        <link rel="icon" href="/favicon.ico?v={{ $appIconVersion }}" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png?v={{ $appIconVersion }}">
        <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16.png?v={{ $appIconVersion }}">

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
        @if ($currentModule?->slug === 'resina')
            @vite('resources/js/resina/index.js')
        @endif
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

                <nav class="-mx-1 min-h-0 flex-1 space-y-1 overflow-y-auto px-1 [scrollbar-width:thin]">
                    @if ($currentModule && $hasMultipleModules)
                        <x-sidebar-link :href="route('dashboard')" icon="arrow-left" :active="false">
                            Torna ai moduli
                        </x-sidebar-link>
                        <div class="!my-3 border-t border-gray-100 dark:border-white/10"></div>
                    @endif

                    @foreach ($navItems as $item)
                        @if (($item['section'] ?? null) && ($item['section'] !== ($navItems[$loop->index - 1]['section'] ?? null)))
                            <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $item['section'] }}</p>
                        @endif
                        <x-sidebar-link :href="route($item['route'], $item['params'] ?? [])" :icon="$item['icon']" :color="$accentColor" :active="$item['active']" :badge="$item['badge'] ?? 0">
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
                        <div class="flex shrink-0 items-center gap-1">
                            @unless ($isAdmin)
                                <x-notification-bell class="h-8 w-8" :open-upward="true" :open-right="true" />
                            @endunless
                            <a href="{{ route('settings.edit') }}" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                <x-heroicon-o-cog-6-tooth class="h-5 w-5" />
                            </a>
                        </div>
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
                    <div class="flex items-center gap-1">
                        @unless ($isAdmin)
                            <x-notification-bell class="h-9 w-9" />
                        @endunless
                        <a href="{{ route('settings.edit') }}" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300">
                            <x-heroicon-o-cog-6-tooth class="h-5 w-5" />
                        </a>
                    </div>
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

        {{--
            Mobile bottom tab bar — scrolls horizontally when there are more
            than ~5 items. Items marked mobile => false go in the "Altro"
            sheet instead (grouped by section), for modules with many pages.
        --}}
        @php
            $moreItems = collect($navItems)->where('mobile', false);
        @endphp
        <div class="lg:hidden" x-data="{ more: false }" @keydown.escape.window="more = false">
            <nav class="fixed inset-x-0 bottom-0 z-20 flex border-t border-gray-100 dark:border-white/10 bg-white/95 dark:bg-gray-900/95 backdrop-blur pb-[env(safe-area-inset-bottom)]">
                <div class="flex w-full justify-evenly gap-2 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach (collect($navItems)->where('mobile', true) as $item)
                        <x-tab-link :href="route($item['route'], $item['params'] ?? [])" :icon="$item['icon']" :color="$accentColor" :active="$item['active']" :badge="$item['badge'] ?? 0">
                            {{ $item['label'] }}
                        </x-tab-link>
                    @endforeach
                    @if ($moreItems->isNotEmpty())
                        <button type="button" @click="more = true"
                                class="flex w-[4.5rem] shrink-0 flex-col items-center justify-center gap-1 py-2 text-xs font-medium {{ $moreItems->contains('active', true) ? $accent['text'] : 'text-gray-400 dark:text-gray-500' }}">
                            <x-heroicon-o-ellipsis-horizontal-circle class="h-6 w-6" />
                            <span>Altro</span>
                        </button>
                    @endif
                </div>
            </nav>

            @if ($moreItems->isNotEmpty())
                <div x-show="more" x-cloak class="fixed inset-0 z-30" role="dialog" aria-modal="true" aria-label="Altre pagine">
                    <div x-show="more" x-transition.opacity class="absolute inset-0 bg-black/40" @click="more = false"></div>
                    <div x-show="more" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                         class="absolute inset-x-0 bottom-0 max-h-[80vh] overflow-y-auto rounded-t-3xl bg-white px-4 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-xl dark:bg-gray-900">
                        <div class="mx-auto mb-3 h-1.5 w-10 rounded-full bg-gray-200 dark:bg-white/15"></div>
                        @foreach ($moreItems->groupBy(fn ($item) => $item['section'] ?? '') as $section => $items)
                            @if ($section !== '')
                                <p class="px-2 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $section }}</p>
                            @endif
                            <div class="space-y-1">
                                @foreach ($items as $item)
                                    <x-sidebar-link :href="route($item['route'], $item['params'] ?? [])" :icon="$item['icon']" :color="$accentColor" :active="$item['active']" :badge="$item['badge'] ?? 0">
                                        {{ $item['label'] }}
                                    </x-sidebar-link>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @livewireScripts
    </body>
</html>
