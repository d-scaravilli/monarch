@php
    $label = $view === 'week'
        ? $days->first()->translatedFormat('d M').' - '.$days->last()->translatedFormat('d M Y')
        : $anchor->translatedFormat('F Y');
@endphp

<x-app-layout>
    <x-slot name="header">Calendario</x-slot>

    <div class="space-y-4">
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="{{ route('palestra.calendar', array_filter(['view' => $view, 'date' => $prevAnchor->toDateString()] + request()->only('course_id', 'room_id'))) }}"
                       class="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5">
                        <x-heroicon-o-chevron-right class="h-4 w-4 rotate-180" />
                    </a>
                    <span class="font-semibold text-gray-900 dark:text-gray-100 capitalize w-48 text-center">{{ $label }}</span>
                    <a href="{{ route('palestra.calendar', array_filter(['view' => $view, 'date' => $nextAnchor->toDateString()] + request()->only('course_id', 'room_id'))) }}"
                       class="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5">
                        <x-heroicon-o-chevron-right class="h-4 w-4" />
                    </a>
                    <a href="{{ route('palestra.calendar', request()->only('course_id', 'room_id')) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        Oggi
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex rounded-xl bg-gray-100 dark:bg-white/5 p-1 text-sm font-medium">
                        <a href="{{ route('palestra.calendar', array_filter(['view' => 'month'] + request()->only('course_id', 'room_id'))) }}"
                           class="rounded-lg px-3 py-1.5 {{ $view === 'month' ? 'bg-white dark:bg-gray-900 shadow-sm' : 'text-gray-500' }}">Mese</a>
                        <a href="{{ route('palestra.calendar', array_filter(['view' => 'week'] + request()->only('course_id', 'room_id'))) }}"
                           class="rounded-lg px-3 py-1.5 {{ $view === 'week' ? 'bg-white dark:bg-gray-900 shadow-sm' : 'text-gray-500' }}">Settimana</a>
                    </div>

                    <form method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="view" value="{{ $view }}">
                        <select name="course_id" onchange="this.form.submit()" class="rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="">Tutti i corsi</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->discipline->name }}</option>
                            @endforeach
                        </select>
                        <select name="room_id" onchange="this.form.submit()" class="rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="">Tutte le sale</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected(request('room_id') == $room->id)>{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </x-card>

        <x-month-calendar-grid :days="$days" :lessons-by-date="$lessonsByDate" :anchor="$anchor" :view="$view" />
    </div>
</x-app-layout>
