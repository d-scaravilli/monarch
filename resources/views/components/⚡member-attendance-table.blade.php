<?php

use App\Models\Attendance;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $memberId;

    public function with(): array
    {
        // Same rule as everywhere else: a lesson before the member's
        // enrollment_date is one they couldn't have attended, so it must
        // never show up here as an absence.
        $attendances = Attendance::query()
            ->join('enrollments', 'attendances.enrollment_id', '=', 'enrollments.id')
            ->join('lessons', 'attendances.lesson_id', '=', 'lessons.id')
            ->join('courses', 'lessons.course_id', '=', 'courses.id')
            ->join('disciplines', 'courses.discipline_id', '=', 'disciplines.id')
            ->where('enrollments.user_id', $this->memberId)
            ->whereColumn('lessons.date', '>=', 'enrollments.enrollment_date')
            ->select(['attendances.*', 'lessons.date as lesson_date', 'disciplines.name as discipline_name'])
            ->orderByDesc('lessons.date')
            ->paginate(10);

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
                        <th class="px-5 py-3 text-left">Corso</th>
                        <th class="px-5 py-3 text-right">Presenza</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($attendances as $attendance)
                        <tr wire:key="attendance-{{ $attendance->id }}">
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::parse($attendance->lesson_date)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $attendance->discipline_name }}</td>
                            <td class="px-5 py-3 text-right">
                                @if ($attendance->present)
                                    <x-heroicon-o-check-circle class="h-4 w-4 text-green-600 inline" />
                                @else
                                    <x-heroicon-o-x-circle class="h-4 w-4 text-red-400 inline" />
                                @endif
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
