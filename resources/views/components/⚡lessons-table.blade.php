<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $courseId = null;

    #[Url]
    public ?int $roomId = null;

    #[Url]
    public ?string $month = null;

    #[Url]
    public bool $allDates = false;

    public string $sortField = 'date';

    public string $sortDirection = 'asc';

    public function mount(?int $courseId = null): void
    {
        $this->courseId = $courseId;
        $this->month = now()->format('Y-m');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'courseId', 'roomId', 'month', 'allDates'])) {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function deleteLesson(int $lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        $this->authorize('update', $lesson->course);

        $lesson->delete();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'courseId', 'roomId', 'allDates']);
        $this->month = now()->format('Y-m');
        $this->resetPage();
    }

    public function with(): array
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        $courseIds = $isAdmin ? null : $user->instructedCourses()->pluck('courses.id');

        $lessons = Lesson::query()
            ->with(['course.discipline', 'course.room', 'attendances'])
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->when($this->search, function ($q) {
                $q->whereHas('course.discipline', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"));
            })
            ->when($this->courseId, fn ($q) => $q->where('course_id', $this->courseId))
            ->when($this->roomId, fn ($q) => $q->whereHas('course', fn ($q2) => $q2->where('room_id', $this->roomId)))
            ->when(! $this->allDates && $this->month, function ($q) {
                $start = \Illuminate\Support\Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
                $q->whereBetween('date', [$start, $start->copy()->endOfMonth()]);
            })
            ->orderBy($this->sortField === 'course' ? 'course_id' : $this->sortField, $this->sortDirection)
            ->paginate(15);

        return [
            'lessons' => $lessons,
            'courses' => Course::with('discipline')
                ->when($courseIds, fn ($q) => $q->whereIn('id', $courseIds))
                ->orderBy('year')
                ->get(),
            'rooms' => Room::orderBy('name')->get(),
        ];
    }
}
?>

<div class="space-y-4">
    <x-card>
        <div class="flex flex-wrap items-end gap-3">
            <div class="relative flex-1 min-w-[10rem]">
                <x-input-label value="Cerca" class="mb-1.5" />
                <x-heroicon-o-magnifying-glass class="absolute left-3 top-[2.35rem] h-4 w-4 text-gray-400" />
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Disciplina..."
                       class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
            </div>

            <div>
                <x-input-label value="Corso" class="mb-1.5" />
                <select wire:model.live="courseId" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">Tutti</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->discipline->name }} ({{ $course->year }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label value="Sala" class="mb-1.5" />
                <select wire:model.live="roomId" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">Tutte</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}">{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>

            <div wire:show="! allDates">
                <x-input-label value="Mese" class="mb-1.5" />
                <input type="month" wire:model.live="month" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 pb-2.5">
                <input type="checkbox" wire:model.live="allDates" class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                Tutte le date
            </label>

            <button type="button" wire:click="resetFilters" class="text-sm font-medium text-gray-500 hover:text-gray-700 pb-2.5">
                Azzera filtri
            </button>
        </div>
    </x-card>

    <x-card class="p-0 overflow-hidden" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">
                            <button wire:click="sortBy('date')" class="flex items-center gap-1 hover:text-gray-600 dark:hover:text-gray-200">
                                Data
                                @if ($lessons->getCollection()->isNotEmpty() && $sortField === 'date')
                                    <x-heroicon-o-chevron-right class="h-3 w-3 {{ $sortDirection === 'asc' ? '-rotate-90' : 'rotate-90' }}" />
                                @endif
                            </button>
                        </th>
                        <th class="px-5 py-3 text-left">
                            <button wire:click="sortBy('course')" class="flex items-center gap-1 hover:text-gray-600 dark:hover:text-gray-200">Corso</button>
                        </th>
                        <th class="px-5 py-3 text-left hidden sm:table-cell">Sala</th>
                        <th class="px-5 py-3 text-left">Presenti/Assenti</th>
                        <th class="px-5 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($lessons as $lesson)
                        <tr wire:key="lesson-{{ $lesson->id }}">
                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $lesson->date->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-300">{{ $lesson->course->discipline->name }}</td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ $lesson->course->room->name }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-2">
                                    <x-badge color="green">{{ $lesson->attendances->where('present', true)->count() }}</x-badge>
                                    <x-badge color="red">{{ $lesson->attendances->where('present', false)->count() }}</x-badge>
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('courses.lessons.attendance.edit', [$lesson->course, $lesson]) }}" class="text-gray-500 hover:text-gray-900 dark:hover:text-white">
                                        <x-heroicon-o-clipboard-document-check class="h-4 w-4" />
                                    </a>
                                    @if (auth()->user()->hasRole('admin'))
                                        <button type="button"
                                                wire:click="deleteLesson({{ $lesson->id }})"
                                                wire:confirm="Eliminare questa lezione? Le presenze registrate andranno perse."
                                                class="text-gray-400 hover:text-red-600">
                                            <x-heroicon-o-trash class="h-4 w-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">Nessuna lezione trovata.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($lessons->hasPages())
        <div>{{ $lessons->links() }}</div>
    @endif
</div>
