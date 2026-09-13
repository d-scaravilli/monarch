@props(['days', 'lessonsByDate', 'anchor', 'view' => 'month', 'mini' => false])
@php
    $weekdayLabels = $mini ? ['L', 'M', 'M', 'G', 'V', 'S', 'D'] : ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    $accent = \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray');
@endphp

<x-card class="p-0 overflow-hidden">
    <div class="grid grid-cols-7 border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
        @foreach ($weekdayLabels as $weekdayLabel)
            <div class="px-1 py-2 text-center">{{ $weekdayLabel }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7">
        @foreach ($days as $day)
            @php
                $inMonth = $view === 'week' || $day->month === $anchor->month;
                $dayLessons = $lessonsByDate->get($day->toDateString(), collect());
                $isToday = $day->isToday();
            @endphp
            <div class="border-b border-r border-gray-100 dark:border-white/10 {{ $mini ? 'min-h-[2.75rem] p-1' : ($view === 'week' ? 'min-h-[10rem] p-2' : 'min-h-[6.5rem] p-2') }} {{ $inMonth ? '' : 'bg-gray-50/50 dark:bg-white/[0.02]' }}">
                <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium
                             {{ $isToday ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : ($inMonth ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300 dark:text-gray-600') }}">
                    {{ $day->day }}
                </span>

                @if ($mini)
                    @if ($dayLessons->isNotEmpty())
                        <div class="mt-1 flex justify-center gap-0.5">
                            @foreach ($dayLessons->take(3) as $lesson)
                                <span class="h-1.5 w-1.5 rounded-full {{ $accent['badge'] }}"></span>
                            @endforeach
                        </div>
                    @endif
                @else
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
                @endif
            </div>
        @endforeach
    </div>
</x-card>
