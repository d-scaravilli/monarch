@php $subtitle = $character->subtitle.($character->alias_it ? ' · nel doppiaggio italiano: '.$character->alias_it : ''); @endphp

<x-app-layout>
    <x-slot name="header">{{ $character->name }}</x-slot>

    <x-resina.payload id="resina-character" :data="$payload" />

    <x-resina.character-sheet payload-id="resina-character" :title="$character->name" :subtitle="$subtitle">
        <x-slot:top>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <a href="{{ route('resina.projects.show', $project) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                    <x-heroicon-o-arrow-left class="h-4 w-4" /> {{ $project->name }}
                </a>
                @if ($isAdmin)
                    <a href="{{ route('resina.characters.edit', [$project, $character]) }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                        <x-heroicon-o-pencil-square class="h-4 w-4" /> Modifica personaggio
                    </a>
                @endif
            </div>
        </x-slot:top>
    </x-resina.character-sheet>

    @if ($isAdmin)
        <x-resina.reference-uploader mode="panel" />
    @endif
</x-app-layout>
