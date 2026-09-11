@php
    $weekdayLabels = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
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

        <x-card class="p-0 overflow-hidden">
            <div class="grid grid-cols-7 border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                @foreach ($weekdayLabels as $label2)
                    <div class="px-3 py-2 text-center">{{ $label2 }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach ($days as $day)
                    @php
                        $inMonth = $view === 'week' || $day->month === $anchor->month;
                        $dayLessons = $lessonsByDate->get($day->toDateString(), collect());
                        $isToday = $day->isToday();
                    @endphp
                    <div class="border-b border-r border-gray-100 dark:border-white/10 {{ $view === 'week' ? 'min-h-[10rem]' : 'min-h-[6.5rem]' }} p-2 {{ $inMonth ? '' : 'bg-gray-50/50 dark:bg-white/[0.02]' }}">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium
                                     {{ $isToday ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : ($inMonth ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300 dark:text-gray-600') }}">
                            {{ $day->day }}
                        </span>

                        <div class="mt-1.5 space-y-1">
                            @foreach ($dayLessons->take($view === 'week' ? 20 : 3) as $lesson)
                                <a href="{{ route('courses.lessons.attendance.edit', [$lesson->course, $lesson]) }}"
                                   class="block truncate rounded-md px-1.5 py-1 text-xs font-medium {{ \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray')['soft'] }} {{ \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray')['text'] }} dark:bg-white/10 dark:text-gray-200">
                                    {{ $lesson->course->discipline->name }}
                                </a>
                            @endforeach
                            @if ($view === 'month' && $dayLessons->count() > 3)
                                <p class="text-xs text-gray-400 px-1.5">+{{ $dayLessons->count() - 3 }} altre</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
</x-app-layout>
