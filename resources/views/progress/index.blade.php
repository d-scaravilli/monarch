<x-app-layout>
    <x-slot name="header">Progressi</x-slot>

    <div class="space-y-8">
        @if ($membersWithGoals->isNotEmpty())
            <div>
                <x-section-header>Con obiettivi in corso</x-section-header>
                <x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($membersWithGoals as $member)
                        @php
                            $initials = mb_strtoupper(collect(explode(' ', $member->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''));
                        @endphp
                        <a href="{{ route('members.show', ['member' => $member, 'tab' => 'progressi']) }}"
                           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-900 dark:bg-white/10 text-xs font-bold text-white">
                                @if ($member->avatarUrl())
                                    <img src="{{ $member->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                                @else
                                    {{ $initials }}
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $member->name }}</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-1 shrink-0">
                                @foreach ($member->enrollments->map(fn ($enrollment) => $enrollment->course->displayName())->unique() as $courseName)
                                    <x-badge>{{ $courseName }}</x-badge>
                                @endforeach
                            </div>
                            <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-gray-300" />
                        </a>
                    @endforeach
                </x-card>
            </div>
        @endif

        <div>
            @if ($membersWithGoals->isNotEmpty())
                <x-section-header>Note recenti</x-section-header>
            @endif

            @if ($members->isEmpty())
                <x-card class="text-center text-gray-500 py-10">
                    Nessuna nota registrata.
                </x-card>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($members as $member)
                        <x-member-progress-card :member="$member" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
