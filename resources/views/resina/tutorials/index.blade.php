<x-app-layout>
    <x-slot name="header">Tutorial video</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Guardare qualcuno che dipinge aiuta più di qualsiasi spiegazione. Questi link aprono ricerche YouTube già impostate.
                Secondo Vallejo, il set Tanned Skin include anche l'accesso a un video tutorial: guarda il foglietto nella confezione.
            </p>
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('resina.tutorials.edit') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                    <x-heroicon-o-pencil-square class="h-4 w-4" /> Modifica
                </a>
            @endif
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tutorials as $tutorial)
                <a href="https://www.youtube.com/results?search_query={{ urlencode($tutorial->query) }}" target="_blank" rel="noopener">
                    <x-card class="flex h-full items-start gap-3 transition hover:ring-gray-300 dark:hover:ring-white/20">
                        <x-heroicon-o-play-circle class="h-6 w-6 shrink-0 text-red-500" />
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $tutorial->title }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $tutorial->description }}</p>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
