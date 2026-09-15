@props(['member'])

@php
    $accent = \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray');
    $initials = collect(explode(' ', $member->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    // notes() is already latest()-ordered, so the first one is the most
    // recent — this card is a preview, the full timeline lives on
    // progress.show.
    $latestNote = $member->notes->first();
@endphp

<a href="{{ route('progress.show', $member) }}" class="block h-full">
    <x-card class="h-full hover:ring-gray-300 dark:hover:ring-white/20 transition">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full {{ $accent['badge'] }} text-xs font-bold text-white">
                @if ($member->avatarUrl())
                    <img src="{{ $member->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                @else
                    {{ mb_strtoupper($initials) }}
                @endif
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $member->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ $member->email }}</p>
            </div>
            <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-300 shrink-0" />
        </div>

        @if ($latestNote)
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-white/10">
                <div class="flex items-center gap-2">
                    <x-badge :color="$latestNote->type === 'infortunio' ? 'red' : 'gray'">{{ $latestNote->typeLabel() }}</x-badge>
                    <span class="text-xs text-gray-400">{{ $latestNote->created_at->translatedFormat('d M Y') }}</span>
                </div>
                <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ $latestNote->description }}</p>
                <p class="mt-2 text-xs font-medium {{ $accent['text'] }}">Apri per vedere tutti i progressi &rarr;</p>
            </div>
        @else
            <p class="mt-3 pt-3 border-t border-gray-100 dark:border-white/10 text-sm text-gray-500">Nessuna nota registrata.</p>
        @endif
    </x-card>
</a>
