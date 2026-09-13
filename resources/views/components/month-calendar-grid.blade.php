@props(['days', 'lessonsByDate', 'anchor', 'view' => 'month', 'mini' => false, 'heading' => null])
@php
    $weekdayLabels = $mini ? ['L', 'M', 'M', 'G', 'V', 'S', 'D'] : ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    $accent = \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray');
@endphp

<x-card class="p-0 overflow-hidden {{ $heading ? 'h-full flex flex-col' : '' }}">
    @if ($heading)
        <div class="px-5 pt-4">
            <x-section-header class="mb-2">{{ $heading }}</x-section-header>
        </div>
    @endif

    <div class="grid grid-cols-7 border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
        @foreach ($weekdayLabels as $weekdayLabel)
            <div class="px-1 py-2 text-center">{{ $weekdayLabel }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 {{ $heading ? 'flex-1 auto-rows-fr' : '' }}">
        @foreach ($days as $day)
            @php
                $inMonth = $view === 'week' || $day->month === $anchor->month;
                // .get() returns lessons keyed by the exact "Y-m-d" string built
                // by the controller's groupBy — confirmed this matches $day's
                // own toDateString() for every cell, mini or full, so the data
                // does reach this component correctly for the right month.
                $dayLessons = $lessonsByDate->get($day->toDateString(), collect());
                $isToday = $day->isToday();
                $hasLessons = $mini && $dayLessons->isNotEmpty();
                // A single lesson goes straight to its own attendance page;
                // more than one is ambiguous, so it opens the full Calendario
                // instead, already filtered (week view) on that date.
                $dayHref = ! $hasLessons ? null : ($dayLessons->count() === 1
                    ? route('courses.lessons.attendance.edit', [$dayLessons->first()->course, $dayLessons->first()])
                    : route('palestra.calendar', ['view' => 'week', 'date' => $day->toDateString()]));
            @endphp
            <div class="border-b border-r border-gray-100 dark:border-white/10 {{ $mini ? 'min-h-[2.75rem] p-1' : ($view === 'week' ? 'min-h-[10rem] p-2' : 'min-h-[6.5rem] p-2') }} {{ $inMonth ? '' : 'bg-gray-50/50 dark:bg-white/[0.02]' }}">
                {{-- The lesson indicator must never share the same circle as
                     "today": when a lesson falls on today, folding both
                     signals into one badge let "today" silently win, so the
                     lesson indicator disappeared on exactly the day someone
                     is most likely to check first. Kept as two independent
                     elements below instead. --}}
                @if ($dayHref)
                    <a href="{{ $dayHref }}" class="block w-fit">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium
                                     {{ $isToday ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : ($inMonth ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300 dark:text-gray-600') }}">
                            {{ $day->day }}
                        </span>
                        <span class="mt-1 flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold text-white {{ $accent['badge'] }}">
                            {{ $dayLessons->count() }}
                        </span>
                    </a>
                @else
                    <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium
                                 {{ $isToday ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : ($inMonth ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300 dark:text-gray-600') }}">
                        {{ $day->day }}
                    </span>
                @endif

                @unless ($mini)
                    <div class="mt-1.5 space-y-1">
                        @foreach ($dayLessons->take($view === 'week' ? 20 : 3) as $lesson)
                            <a href="{{ route('courses.lessons.attendance.edit', [$lesson->course, $lesson]) }}"
                               class="block truncate rounded-md px-1.5 py-1 text-xs font-medium {{ $accent['soft'] }} {{ $accent['text'] }} dark:bg-white/10 dark:text-gray-200">
                                {{ $lesson->course->discipline->name }}
                            </a>
                        @endforeach
                        @if ($view === 'month' && $dayLessons->count() > 3)
                            <p class="text-xs text-gray-400 px-1.5">+{{ $dayLessons->count() - 3 }} altre</p>
                        @endif
                    </div>
                @endunless
            </div>
        @endforeach
    </div>
</x-card>
