@props(['notes'])

@php
    $accent = \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray');

    $typeIcon = [
        'progresso' => 'arrow-trending-up',
        'tecnica' => 'academic-cap',
        'infortunio' => 'exclamation-triangle',
        'altro' => 'information-circle',
    ];
@endphp

@if ($notes->isEmpty())
    <x-card class="text-center text-gray-500 py-10">
        Nessuna nota registrata.
    </x-card>
@else
    <div class="relative">
        <div class="absolute left-[13px] top-3 bottom-3 w-px bg-gray-200 dark:bg-white/10"></div>

        <div class="space-y-6">
            @foreach ($notes as $note)
                @php $isInjury = $note->type === 'infortunio'; @endphp
                <div class="relative pl-10">
                    <span class="absolute left-0 top-0 z-10 flex h-7 w-7 items-center justify-center rounded-full ring-4 ring-gray-50 dark:ring-gray-950 {{ $isInjury ? 'bg-red-500' : $accent['badge'] }}">
                        <x-dynamic-component :component="'heroicon-o-'.($typeIcon[$note->type] ?? 'information-circle')" class="h-3.5 w-3.5 text-white" />
                    </span>

                    <x-card>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :color="$isInjury ? 'red' : 'gray'">{{ $note->typeLabel() }}</x-badge>
                            <span class="text-xs text-gray-400">
                                {{ $note->created_at->translatedFormat('d M Y') }}
                                &middot; {{ $note->author->name }}
                                @if ($note->lesson?->course?->discipline)
                                    &middot; {{ $note->lesson->course->discipline->name }}
                                @endif
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $note->description }}</p>
                    </x-card>
                </div>
            @endforeach
        </div>
    </div>
@endif
