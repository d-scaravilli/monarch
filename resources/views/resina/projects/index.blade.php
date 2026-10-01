<x-app-layout>
    <x-slot name="header">Progetti</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Ogni progetto ha le schede colore dei personaggi e, dove serve, le armature e le procedure passo passo. Le
                tecniche e il ricettario sono in comune a tutti i progetti.
            </p>
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('resina.projects.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">
                    <x-heroicon-o-plus class="h-4 w-4" /> Nuovo progetto
                </a>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($projects as $project)
                <x-resina.project-card :project="$project" />
            @empty
                <x-card class="text-sm text-gray-500 sm:col-span-2 lg:col-span-3">Nessun progetto.</x-card>
            @endforelse
        </div>
    </div>
</x-app-layout>
