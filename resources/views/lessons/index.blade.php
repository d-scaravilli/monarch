@php
    $prevMonth = $from ? $from->copy()->subMonthNoOverflow()->startOfMonth() : null;
    $nextMonth = $from ? $from->copy()->addMonthNoOverflow()->startOfMonth() : null;
@endphp

<x-app-layout>
    <x-slot name="header">Lezioni</x-slot>

    @if (auth()->user()->hasRole('admin'))
        <div class="flex justify-end gap-2 mb-4">
            <a href="{{ route('lessons.generate') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <x-heroicon-o-square-3-stack-3d class="h-4 w-4" />
                Genera lezioni
            </a>
            <a href="{{ route('lessons.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
                <x-heroicon-o-plus class="h-4 w-4" />
                Nuova lezione
            </a>
        </div>
    @endif

    <x-card class="mb-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="space-y-1.5">
                <x-input-label value="Corso" />
                <select name="course_id" onchange="this.form.submit()" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">Tutti</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->discipline->name }} ({{ $course->year }})</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1.5">
                <x-input-label value="Sala" />
                <select name="room_id" onchange="this.form.submit()" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">Tutte</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(request('room_id') == $room->id)>{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1.5">
                <x-input-label value="Da" />
                <x-text-input type="date" name="from" value="{{ request('from') }}" />
            </div>
            <div class="space-y-1.5">
                <x-input-label value="A" />
                <x-text-input type="date" name="to" value="{{ request('to') }}" />
            </div>
            <button type="submit" class="rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200">Filtra</button>

            @if (! $showAll)
                <a href="{{ route('lessons.index', ['all' => 1] + request()->only('course_id', 'room_id')) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Mostra tutte</a>
            @else
                <a href="{{ route('lessons.index', request()->only('course_id', 'room_id')) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Solo mese corrente</a>
            @endif
        </form>

        @if ($from && $to && ! request()->filled('from'))
            <div class="mt-3 flex items-center gap-3 text-sm">
                <a href="{{ route('lessons.index', ['from' => $prevMonth->toDateString(), 'to' => $prevMonth->copy()->endOfMonth()->toDateString()] + request()->only('course_id', 'room_id')) }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $from->translatedFormat('F Y') }}</span>
                <a href="{{ route('lessons.index', ['from' => $nextMonth->toDateString(), 'to' => $nextMonth->copy()->endOfMonth()->toDateString()] + request()->only('course_id', 'room_id')) }}" class="text-gray-500 hover:text-gray-700">&rarr;</a>
            </div>
        @endif
    </x-card>

    <div class="overflow-x-auto">
        <x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10 min-w-[40rem]">
            <div class="grid grid-cols-5 gap-2 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                <span>Data</span>
                <span>Corso</span>
                <span>Sala</span>
                <span>Presenti/Assenti</span>
                <span class="text-right">Azioni</span>
            </div>
            @forelse ($lessons as $lesson)
                <div class="grid grid-cols-5 gap-2 px-5 py-3.5 items-center text-sm">
                    <span class="text-gray-700 dark:text-gray-300">{{ $lesson->date->translatedFormat('d M Y') }}</span>
                    <span class="text-gray-700 dark:text-gray-300">{{ $lesson->course->discipline->name }}</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ $lesson->course->room->name }}</span>
                    <span class="flex items-center gap-2">
                        <x-badge color="green">{{ $lesson->attendances->where('present', true)->count() }}</x-badge>
                        <x-badge color="red">{{ $lesson->attendances->where('present', false)->count() }}</x-badge>
                    </span>
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('courses.lessons.attendance.edit', [$lesson->course, $lesson]) }}" class="text-gray-500 hover:text-gray-900 dark:hover:text-white">
                            <x-heroicon-o-clipboard-document-check class="h-4 w-4" />
                        </a>
                        @if (auth()->user()->hasRole('admin'))
                            <form method="POST" action="{{ route('lessons.destroy', $lesson) }}" onsubmit="return confirm('Eliminare questa lezione? Le presenze registrate andranno perse.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-gray-500">Nessuna lezione nel periodo selezionato.</p>
            @endforelse
        </x-card>
    </div>
</x-app-layout>
