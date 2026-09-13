@props(['member'])

@php
    $accent = \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray');
    $initials = collect(explode(' ', $member->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10">
    <div class="flex items-center gap-3 px-5 py-4">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $accent['badge'] }} text-xs font-bold text-white">
            {{ mb_strtoupper($initials) }}
        </span>
        <div class="min-w-0">
            <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $member->name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ $member->email }}</p>
        </div>
    </div>

    @forelse ($member->notes as $note)
        <div class="px-5 py-3.5">
            <div class="flex items-center gap-2">
                <x-badge :color="$note->type === 'infortunio' ? 'red' : 'gray'">{{ $note->typeLabel() }}</x-badge>
                <span class="text-xs text-gray-400">
                    {{ $note->created_at->translatedFormat('d M Y') }}
                    &middot; {{ $note->author->name }}
                    @if ($note->lesson?->course?->discipline)
                        &middot; {{ $note->lesson->course->discipline->name }}
                    @endif
                </span>
            </div>
            <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $note->description }}</p>
        </div>
    @empty
        <p class="px-5 py-6 text-sm text-gray-500">Nessuna nota registrata.</p>
    @endforelse
</x-card>
