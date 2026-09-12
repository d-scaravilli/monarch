<?php

use App\Models\Enrollment;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $courseId;

    public string $accentColor = 'gray';

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
            ->with(['user', 'course', 'attendances', 'payments'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(5);

        return [
            'enrollments' => $enrollments,
            'accent' => \App\Support\ModuleTheme::classes($this->accentColor),
        ];
    }
}
?>

<div class="space-y-3">
    <x-card class="p-0 overflow-hidden" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">
                            <button wire:click="sortBy('enrollment_date')" class="hover:text-gray-600 dark:hover:text-gray-200">Iscritto</button>
                        </th>
                        <th class="px-5 py-3 text-left hidden sm:table-cell">Email</th>
                        <th class="px-5 py-3 text-left hidden md:table-cell">Data iscrizione</th>
                        <th class="px-5 py-3 text-left">Presenze/Assenze</th>
                        <th class="px-5 py-3 text-left">Costi</th>
                        <th class="px-5 py-3 text-left">Stato</th>
                        <th class="px-5 py-3 text-right">Azioni</th>
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
                                <a href="{{ route('members.show', $enrollment->user) }}" class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $accent['badge'] }} text-xs font-bold text-white">
                                        {{ mb_strtoupper($initials) }}
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->user->name }}</span>
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ $enrollment->user->email }}</td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden md:table-cell whitespace-nowrap">{{ $enrollment->enrollment_date->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-2">
                                    <x-badge color="green">{{ $enrollment->attendances->where('present', true)->count() }}</x-badge>
                                    <x-badge color="red">{{ $enrollment->attendances->where('present', false)->count() }}</x-badge>
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
                            <td class="px-5 py-3.5">
                                <x-badge :color="$enrollment->status === 'active' ? 'green' : 'gray'">{{ $enrollment->status }}</x-badge>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if (auth()->user()->hasRole('admin'))
                                    <button type="button" wire:click="deleteEnrollment({{ $enrollment->id }})"
                                            wire:confirm="Rimuovere questa iscrizione?"
                                            class="text-gray-400 hover:text-red-600">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">Nessun iscritto.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($enrollments->hasPages())
        <div>{{ $enrollments->links() }}</div>
    @endif
</div>
