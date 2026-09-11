<x-app-layout>
    <x-slot name="header">Corsi</x-slot>
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
                <a href="{{ route('courses.show', $course) }}">
                    <x-card class="h-full hover:ring-gray-300 dark:hover:ring-white/20 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $course->discipline->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $course->room->name }} &middot; {{ $course->year }}</p>
                            </div>
                            <x-heroicon-o-chevron-right class="h-5 w-5 text-gray-300 shrink-0" />
                        </div>

                        <p class="mt-4 text-3xl font-bold {{ $accent['text'] }}">{{ $course->enrollments_count }}</p>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $course->enrollments_count === 1 ? 'iscritto' : 'iscritti' }}</p>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
