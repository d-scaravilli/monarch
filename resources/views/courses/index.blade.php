<x-app-layout>
    <x-slot name="header">Corsi/Eventi</x-slot>
    @php $accent = \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray'); @endphp

    @if (auth()->user()->hasRole('admin'))
        <div class="flex justify-end mb-4">
            <a href="{{ route('courses.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
                <x-heroicon-o-plus class="h-4 w-4" />
                Nuovo corso
            </a>
        </div>
    @endif

    @if ($courses->isEmpty())
        <x-card class="text-center text-gray-500 py-10">
            Nessun corso disponibile.
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($courses as $course)
                @php $fillPercent = min(100, $course->room->capacity > 0 ? round($course->enrollments_count / $course->room->capacity * 100) : 0); @endphp
                <a href="{{ route('courses.show', $course) }}">
                    <x-card class="h-full hover:ring-gray-300 dark:hover:ring-white/20 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                                    {{ $course->discipline->name }}
                                    <x-badge :color="$course->isEvento() ? 'purple' : 'gray'">{{ $course->isEvento() ? 'Evento' : 'Corso' }}</x-badge>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $course->room->name }} &middot; {{ $course->year }}</p>
                                @if ($course->schedules->isNotEmpty())
                                    <p class="mt-1 text-xs text-gray-400 truncate">{{ $course->scheduleSummary() }}</p>
                                @endif
                            </div>
                            <x-heroicon-o-chevron-right class="h-5 w-5 text-gray-300 shrink-0" />
                        </div>

                        @if ($course->instructors->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($course->instructors as $instructor)
                                    <x-badge>{{ $instructor->name }}</x-badge>
                                @endforeach
                            </div>
                        @endif

                        <p class="mt-4 text-3xl font-bold {{ $accent['text'] }}">{{ $course->enrollments_count }}</p>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $course->enrollments_count === 1 ? 'iscritto' : 'iscritti' }}</p>

                        <div class="mt-4">
                            <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full {{ $accent['badge'] }}" style="width: {{ $fillPercent }}%"></div>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400">{{ $course->enrollments_count }}/{{ $course->room->capacity }} posti sala</p>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
