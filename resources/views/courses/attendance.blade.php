@php
    $initial = $course->enrollments->mapWithKeys(fn ($e) => [$e->id => (bool) ($attendances[$e->id] ?? false)]);
@endphp

<x-app-layout>
    <x-slot name="header">Presenze &middot; {{ $lesson->date->translatedFormat('d M Y') }}</x-slot>

    <div
        x-data="{
            state: {{ Illuminate\Support\Js::from($initial) }},
            saved: null,
            toggleUrl: '{{ route('courses.lessons.attendance.toggle', [$course, $lesson]) }}',
            markAllUrl: '{{ route('courses.lessons.attendance.mark-all', [$course, $lesson]) }}',
            async toggle(id) {
                this.state[id] = !this.state[id];
                this.saved = id;
                await fetch(this.toggleUrl, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                    body: JSON.stringify({ enrollment_id: id, present: this.state[id] }),
                });
                setTimeout(() => { if (this.saved === id) this.saved = null }, 900);
            },
            async markAllPresent() {
                for (const id in this.state) {
                    this.state[id] = true;
                }
                await fetch(this.markAllUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                });
            },
        }"
        class="space-y-4"
    >
        <button
            type="button"
            @click="markAllPresent()"
            class="flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-300"
        >
            <x-heroicon-o-check-circle class="h-4 w-4" />
            Segna tutti presenti
        </button>

        <x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($course->enrollments as $enrollment)
                <div class="flex items-center justify-between px-5 py-4">
                    <div class="flex items-center gap-2">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $enrollment->user->email }}</p>
                        </div>
                        <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" x-show="saved === {{ $enrollment->id }}" x-cloak />
                    </div>

                    <button
                        type="button"
                        @click="toggle({{ $enrollment->id }})"
                        :class="state[{{ $enrollment->id }}] ? 'bg-gray-900 dark:bg-white' : 'bg-gray-200 dark:bg-white/10'"
                        class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors"
                    >
                        <span :class="state[{{ $enrollment->id }}] ? 'translate-x-6' : 'translate-x-1'"
                              class="inline-block h-5 w-5 transform rounded-full bg-white dark:bg-gray-900 transition-transform"></span>
                    </button>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-gray-500">Nessun iscritto per questo corso.</p>
            @endforelse
        </x-card>
    </div>

    <a href="{{ route('courses.show', $course) }}" class="mt-4 inline-block text-sm font-medium text-gray-500 hover:text-gray-700">&larr; Torna al corso</a>
</x-app-layout>
