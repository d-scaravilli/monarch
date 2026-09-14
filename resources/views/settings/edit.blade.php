@php
    $user = auth()->user();

    // Land on whichever tab a failed submission belongs to, so its error
    // messages (and, for the delete-account modal, the reopened modal
    // itself) are actually visible instead of sitting inside a hidden tab.
    $initialTab = 'account';
    if ($errors->has('avatar')) {
        $initialTab = 'appearance';
    } elseif ($errors->updatePassword->isNotEmpty()) {
        $initialTab = 'security';
    } elseif ($errors->userDeletion->isNotEmpty()) {
        $initialTab = 'danger';
    } elseif ($errors->any()) {
        $initialTab = 'account';
    }
@endphp

<x-app-layout>
    <x-slot name="header">Impostazioni</x-slot>

    <div class="max-w-xl space-y-6" x-data="{ tab: '{{ $initialTab }}' }">
        @if ($accessibleModules->isNotEmpty())
            <div>
                <x-section-header>Moduli</x-section-header>
                <x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($accessibleModules as $module)
                        <a href="{{ route('modules.enter', $module) }}" class="flex items-center gap-3 px-5 py-3.5">
                            <x-module-badge :icon="$module->icon" :color="$module->color" :image="$module->imageUrl()" size="h-9 w-9" />
                            <span class="flex-1 font-medium text-gray-900 dark:text-gray-100">{{ $module->name }}</span>
                            <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-300" />
                        </a>
                    @endforeach
                </x-card>
            </div>
        @endif

        <div class="inline-flex flex-wrap gap-1 rounded-xl bg-gray-100 dark:bg-white/5 p-1 text-sm font-medium">
            <button type="button" @click="tab = 'account'" class="rounded-lg px-4 py-2 transition" :class="tab === 'account' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Account</button>
            <button type="button" @click="tab = 'security'" class="rounded-lg px-4 py-2 transition" :class="tab === 'security' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Sicurezza</button>
            <button type="button" @click="tab = 'appearance'" class="rounded-lg px-4 py-2 transition" :class="tab === 'appearance' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Aspetto</button>
            <button type="button" @click="tab = 'notifications'" class="rounded-lg px-4 py-2 transition" :class="tab === 'notifications' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Notifiche</button>
            @if ($user->hasRole('admin'))
                <button type="button" @click="tab = 'danger'" class="rounded-lg px-4 py-2 transition" :class="tab === 'danger' ? 'bg-white dark:bg-gray-900 shadow-sm text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">Zona pericolosa</button>
            @endif
        </div>

        <div x-show="tab === 'account'" x-cloak class="space-y-6">
            @include('settings.partials.account-form')
        </div>

        <div x-show="tab === 'security'" x-cloak class="space-y-6">
            @include('settings.partials.password-form')
        </div>

        <div x-show="tab === 'appearance'" x-cloak class="space-y-6">
            @include('settings.partials.avatar-form')

            <div>
                <x-section-header>Tema</x-section-header>

                <x-card
                    class="p-0 divide-y divide-gray-100 dark:divide-white/10"
                    x-data="{
                        theme: '{{ $user->theme }}',
                        saved: false,
                        async set(value) {
                            this.theme = value;
                            document.documentElement.classList.toggle('dark', value === 'dark' || (value === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches));
                            document.documentElement.dataset.themePref = value;
                            await fetch('{{ route('settings.theme') }}', {
                                method: 'PATCH',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                                body: JSON.stringify({ theme: value }),
                            });
                            this.saved = true;
                            setTimeout(() => (this.saved = false), 1200);
                        },
                    }"
                >
                    @foreach ([['light', 'sun', 'Chiaro'], ['dark', 'moon', 'Scuro'], ['auto', 'computer-desktop', 'Automatico']] as [$value, $icon, $label])
                        <button
                            type="button"
                            @click="set('{{ $value }}')"
                            class="flex w-full items-center justify-between px-5 py-4 text-left"
                        >
                            <span class="flex items-center gap-3">
                                <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5 text-gray-400" />
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $label }}</span>
                            </span>
                            <x-heroicon-o-check-circle class="h-5 w-5 text-gray-900 dark:text-white" x-show="theme === '{{ $value }}'" x-cloak />
                        </button>
                    @endforeach
                </x-card>

                <p class="mt-1.5 text-xs text-gray-400">
                    La preferenza è salvata sul tuo account e vale su tutti i dispositivi.
                </p>
            </div>
        </div>

        <div
            x-show="tab === 'notifications'" x-cloak class="space-y-6"
            x-data="{
                installed: false,
                supported: false,
                permission: 'default',
                subscribed: false,
                loading: false,
                async init() {
                    this.installed = window.MonarchPush?.isRunningInstalled() ?? false;
                    this.supported = window.MonarchPush?.isSupported() ?? false;
                    this.permission = window.MonarchPush?.permission() ?? 'unsupported';
                    if (this.installed && this.supported) {
                        const sub = await window.MonarchPush.getSubscription();
                        this.subscribed = !!sub;
                    }
                },
                async enable() {
                    this.loading = true;
                    const result = await window.MonarchPush.subscribe();
                    this.permission = result.status;
                    this.subscribed = result.status === 'granted';
                    this.loading = false;
                },
                async disable() {
                    this.loading = true;
                    await window.MonarchPush.unsubscribe();
                    this.subscribed = false;
                    this.loading = false;
                },
            }"
        >
            <div>
                <x-section-header>Notifiche push</x-section-header>

                <template x-if="!installed">
                    <x-card class="flex items-start gap-3">
                        <x-heroicon-o-information-circle class="h-5 w-5 text-gray-400 shrink-0 mt-0.5" />
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Installa l'app sulla schermata Home per ricevere le notifiche. Su iPhone: apri questo sito
                            da Safari, tocca "Condividi" e poi "Aggiungi alla schermata Home".
                        </p>
                    </x-card>
                </template>

                <template x-if="installed && !supported">
                    <x-card>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Questo browser non supporta le notifiche push.</p>
                    </x-card>
                </template>

                <template x-if="installed && supported">
                    <x-card class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-show="subscribed">Notifiche attive</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-show="!subscribed">Notifiche disattivate</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-show="permission === 'denied'" x-cloak>
                                Il permesso è stato negato dal browser: riattivalo dalle impostazioni del sito per attivarle.
                            </p>
                        </div>
                        <button type="button" x-show="!subscribed" :disabled="loading || permission === 'denied'" @click="enable()"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40">
                            Attiva
                        </button>
                        <button type="button" x-show="subscribed" x-cloak :disabled="loading" @click="disable()"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200">
                            Disattiva
                        </button>
                    </x-card>
                </template>
            </div>
        </div>

        @if ($user->hasRole('admin'))
            <div x-show="tab === 'danger'" x-cloak class="space-y-6">
                @include('settings.partials.delete-account-form')
            </div>
        @endif

        {{-- Desktop already has logout in the sidebar footer; mobile has no other way out. --}}
        <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
            @csrf
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white dark:bg-gray-900 px-5 py-3 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-gray-100 dark:ring-white/10">
                <x-heroicon-o-arrow-right-start-on-rectangle class="h-5 w-5" />
                Esci
            </button>
        </form>
    </div>
</x-app-layout>
