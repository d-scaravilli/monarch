<x-app-layout>
    <x-slot name="header">Impostazioni</x-slot>

    <div class="max-w-xl">
        <x-section-header>Tema</x-section-header>

        <x-card
            class="p-0 divide-y divide-gray-100 dark:divide-white/10"
            x-data="{
                theme: '{{ auth()->user()->theme }}',
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

        <p class="mt-3 text-xs text-gray-400">
            La preferenza è salvata sul tuo account e vale su tutti i dispositivi.
        </p>
    </div>
</x-app-layout>
