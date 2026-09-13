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
        ];
    }
}
?>

@php
    $bgImage = $currentModule?->enrollmentsBackgroundUrl();
    $mutedText = $bgImage ? 'text-white/70' : 'text-gray-400';
    $bodyText = $bgImage ? 'text-white' : 'text-gray-900 dark:text-gray-100';
    $secondaryText = $bgImage ? 'text-white/70' : 'text-gray-500 dark:text-gray-400';
    $divide = $bgImage ? 'divide-white/10' : 'divide-gray-100 dark:divide-white/10';
    $border = $bgImage ? 'border-white/10' : 'border-gray-100 dark:border-white/10';
    $actionIcon = $bgImage ? 'text-white/60 hover:text-red-300' : 'text-gray-400 hover:text-red-600';
@endphp

<div class="space-y-3">
    <div
        @if ($bgImage)
            style="background-image: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('{{ $bgImage }}'); background-size: cover; background-position: center;"
        @endif
        class="rounded-2xl overflow-hidden shadow-sm ring-1 ring-gray-100 dark:ring-white/10 {{ $bgImage ? '' : $accent['soft'].' dark:bg-white/5' }}"
        wire:loading.class="opacity-60"
    >
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b {{ $border }} text-xs font-semibold uppercase tracking-wide {{ $mutedText }}">
                        <th class="px-5 py-3 text-left">
                            <button wire:click="sortBy('enrollment_date')" class="hover:text-gray-600 dark:hover:text-gray-200">Iscritto</button>
                        </th>
                        @unless ($compact)
                            <th class="px-5 py-3 text-left hidden sm:table-cell">Email</th>
                            <th class="px-5 py-3 text-left hidden md:table-cell">Data iscrizione</th>
                            <th class="px-5 py-3 text-left">Presenze/Assenze</th>
                            <th class="px-5 py-3 text-left">Costi</th>
                        @endunless
                        <th class="px-5 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y {{ $divide }}">
                    @forelse ($enrollments as $enrollment)
                        @php
                            $initials = collect(explode(' ', $enrollment->user->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                            $balance = $enrollment->balance();
                            $status = $enrollment->balanceStatus();
                        @endphp
                        <tr wire:key="enrollment-{{ $enrollment->id }}">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('members.show', $enrollment->user) }}" class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $accent['badge'] }} text-xs font-bold text-white">
                                        {{ mb_strtoupper($initials) }}
                                    </span>
                                    <span class="font-medium {{ $bodyText }}">{{ $enrollment->user->name }}</span>
                                </a>
                            </td>
                            @unless ($compact)
                                <td class="px-5 py-3.5 {{ $secondaryText }} hidden sm:table-cell">{{ $enrollment->user->email }}</td>
                                <td class="px-5 py-3.5 {{ $secondaryText }} hidden md:table-cell whitespace-nowrap">{{ $enrollment->enrollment_date->translatedFormat('d M Y') }}</td>
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
                            <td class="px-5 py-3.5 text-right">
                                @if (auth()->user()->hasRole('admin'))
                                    <button type="button" wire:click="deleteEnrollment({{ $enrollment->id }})"
                                            wire:confirm="Rimuovere questa iscrizione?"
                                            class="{{ $actionIcon }}">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $compact ? 2 : 6 }}" class="px-5 py-8 text-center text-sm {{ $secondaryText }}">Nessun iscritto.</td>
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
