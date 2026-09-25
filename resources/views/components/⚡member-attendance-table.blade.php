<?php

use App\Models\Attendance;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $memberId;

    /**
     * "corso" or "evento": the list only ever shows one of the two, so
     * lessons of courses and events never mix in the same list.
     */
    public string $type = 'corso';

    public function with(): array
    {
        // Same rule as everywhere else: a lesson before the member's
        // enrollment_date is one they couldn't have attended, so it must
        // never show up here as an absence.
        $attendances = Attendance::query()
            ->with(['lesson.course' => fn ($q) => $q->withTrashed()->with('discipline')])
            ->join('enrollments', 'attendances.enrollment_id', '=', 'enrollments.id')
            ->join('lessons', 'attendances.lesson_id', '=', 'lessons.id')
            ->join('courses', 'lessons.course_id', '=', 'courses.id')
            ->where('enrollments.user_id', $this->memberId)
            ->where('courses.type', $this->type)
            ->whereColumn('lessons.date', '>=', 'enrollments.enrollment_date')
            ->select(['attendances.*', 'lessons.date as lesson_date', 'lessons.course_id'])
            ->orderByDesc('lessons.date')
            ->paginate(10, pageName: $this->type.'Page');

        return ['attendances' => $attendances];
    }
}
?>

<div class="space-y-3">
    <x-card class="p-0 overflow-hidden" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Data</th>
                        <th class="px-5 py-3 text-left">{{ $type === 'evento' ? 'Evento' : 'Corso' }}</th>
                        <th class="px-5 py-3 text-right">Presenza</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($attendances as $attendance)
                        @php
                            $lessonUrl = route('courses.lessons.attendance.edit', [$attendance->course_id, $attendance->lesson_id]);
                        @endphp
                        <tr wire:key="attendance-{{ $attendance->id }}" onclick="window.location='{{ $lessonUrl }}'"
                            class="cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::parse($attendance->lesson_date)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $attendance->lesson->course->displayName() }}</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center gap-3">
                                    @if ($attendance->present)
                                        <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" />
                                    @else
                                        <x-heroicon-o-x-circle class="h-4 w-4 text-red-400" />
                                    @endif
                                    <a href="{{ $lessonUrl }}" onclick="event.stopPropagation()"
                                       class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-900 dark:hover:text-gray-100">
                                        Apri
                                        <x-heroicon-o-chevron-right class="h-3.5 w-3.5" />
                                    </a>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-gray-500">Nessuna presenza registrata.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($attendances->hasPages())
        <div>{{ $attendances->links() }}</div>
    @endif
</div>
