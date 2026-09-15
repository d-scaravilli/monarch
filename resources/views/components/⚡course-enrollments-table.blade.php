<?php

use App\Models\Enrollment;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $courseId;

    public string $accentColor = 'gray';

    /**
     * An evento's roster only needs a name to check who's coming — no
     * email/dates/attendance/costs, which don't mean much for a one-off.
     */
    public bool $compact = false;

    public string $sortField = 'enrollment_date';

    public string $sortDirection = 'desc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function deleteEnrollment(int $enrollmentId): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        Enrollment::findOrFail($enrollmentId)->delete();
    }

    public function with(): array
    {
        $enrollments = Enrollment::query()
            ->where('course_id', $this->courseId)
            ->with($this->compact ? ['user'] : ['user', 'course', 'attendances.lesson', 'payments'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(5);

        return [
            'enrollments' => $enrollments,
            'accent' => \App\Support\ModuleTheme::classes($this->accentColor),
            'canDelete' => auth()->user()->hasRole('admin'),
        ];
    }
}
?>

<div class="space-y-3">
    <div class="rounded-2xl overflow-hidden shadow-sm ring-1 ring-gray-100 dark:ring-white/10 {{ $accent['soft'] }} dark:bg-white/5" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">
                            <button wire:click="sortBy('enrollment_date')" class="hover:text-gray-600 dark:hover:text-gray-200">Iscritto</button>
                        </th>
                        @unless ($compact)
                            <th class="px-5 py-3 text-left hidden sm:table-cell">Email</th>
                            <th class="px-5 py-3 text-left hidden md:table-cell">Data iscrizione</th>
                            <th class="px-5 py-3 text-left">Presenze/Assenze</th>
                            <th class="px-5 py-3 text-left">Costi</th>
                        @endunless
                        @if ($canDelete)
                            <th class="px-5 py-3 text-right">Azioni</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($enrollments as $enrollment)
                        @php
                            $initials = collect(explode(' ', $enrollment->user->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                            $balance = $enrollment->balance();
                            $status = $enrollment->balanceStatus();
                        @endphp
                        <tr wire:key="enrollment-{{ $enrollment->id }}">
                            <td class="px-5 py-3.5">
                                @if (auth()->user()->can('view', $enrollment->user))
                                    <a href="{{ route('members.show', $enrollment->user) }}" class="flex items-center gap-2.5">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full {{ $accent['badge'] }} text-xs font-bold text-white">
                                            @if ($enrollment->user->avatarUrl())
                                                <img src="{{ $enrollment->user->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                                            @else
                                                {{ mb_strtoupper($initials) }}
                                            @endif
                                        </span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->user->name }}</span>
                                    </a>
                                @else
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full {{ $accent['badge'] }} text-xs font-bold text-white">
                                            @if ($enrollment->user->avatarUrl())
                                                <img src="{{ $enrollment->user->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                                            @else
                                                {{ mb_strtoupper($initials) }}
                                            @endif
                                        </span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->user->name }}</span>
                                    </div>
                                @endif
                            </td>
                            @unless ($compact)
                                <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ $enrollment->user->email }}</td>
                                <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden md:table-cell whitespace-nowrap">{{ $enrollment->enrollment_date->translatedFormat('d M Y') }}</td>
                                @php $validAttendances = $enrollment->validAttendances(); @endphp
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-2">
                                        <x-badge color="green">{{ $validAttendances->where('present', true)->count() }}</x-badge>
                                        <x-badge color="red">{{ $validAttendances->where('present', false)->count() }}</x-badge>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if ($status === 'missing')
                                        <x-badge color="amber">Mancano €{{ number_format(abs($balance), 2) }}</x-badge>
                                    @elseif ($status === 'overpaid')
                                        <x-badge color="green">+€{{ number_format($balance, 2) }}</x-badge>
                                    @else
                                        <x-badge color="green">In pari</x-badge>
                                    @endif
                                </td>
                            @endunless
                            @if ($canDelete)
                                <td class="px-5 py-3.5 text-right">
                                    <button type="button" wire:click="deleteEnrollment({{ $enrollment->id }})"
                                            wire:confirm="Rimuovere questa iscrizione?"
                                            class="text-gray-400 hover:text-red-600">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($compact ? 1 : 5) + ($canDelete ? 1 : 0) }}" class="px-5 py-8 text-center text-sm text-gray-500">Nessun iscritto.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($enrollments->hasPages())
        <div>{{ $enrollments->links() }}</div>
    @endif
</div>
