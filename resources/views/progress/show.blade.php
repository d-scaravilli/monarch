@php
    $accent = \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray');
    $initials = collect(explode(' ', $member->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $canBrowseAll = auth()->user()->can('viewAny', \App\Models\User::class);
@endphp

<x-app-layout>
    <x-slot name="header">Progressi &middot; {{ $member->name }}</x-slot>

    <div class="max-w-3xl">
        <div class="flex items-center gap-3 mb-6">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full {{ $accent['badge'] }} text-sm font-bold text-white">
                @if ($member->avatarUrl())
                    <img src="{{ $member->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                @else
                    {{ mb_strtoupper($initials) }}
                @endif
            </span>
            <div class="min-w-0">
                <p class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $member->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ $member->email }}</p>
            </div>
        </div>

        @if ($member->enrollments->isNotEmpty())
            <div class="space-y-4 mb-6">
                @foreach ($member->enrollments as $enrollment)
                    <x-enrollment-goals :enrollment="$enrollment" :can-manage="auth()->user()->can('manageAttendance', $enrollment->course)" />
                @endforeach
            </div>
        @endif

        <x-progress-timeline :notes="$member->notes" />

        <a href="{{ $canBrowseAll ? route('progress.index') : route('member.area') }}" class="mt-6 inline-block text-sm font-medium text-gray-500 hover:text-gray-700">
            &larr; {{ $canBrowseAll ? 'Torna a Progressi' : 'Torna a La mia area' }}
        </a>
    </div>
</x-app-layout>
