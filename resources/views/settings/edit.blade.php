@php $user = auth()->user(); @endphp

<x-app-layout>
    <x-slot name="header">Impostazioni</x-slot>

    <div class="max-w-xl space-y-6">
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

        @include('settings.partials.avatar-form')

        @include('settings.partials.account-form')

        @include('settings.partials.password-form')

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

        @if ($user->hasRole('admin'))
            @include('settings.partials.delete-account-form')
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
