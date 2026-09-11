@php
    $canManageAttendance = auth()->user()->can('manageAttendance', $courseEdition);
@endphp

<x-app-layout>
    <x-slot name="header">{{ $courseEdition->course->name }}</x-slot>

    <div class="space-y-6">
        <x-card>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-600">
                <span class="flex items-center gap-1.5"><x-heroicon-o-home class="h-4 w-4" /> {{ $courseEdition->room->name }}</span>
                <span class="flex items-center gap-1.5"><x-heroicon-o-calendar-days class="h-4 w-4" /> {{ $courseEdition->year }}</span>
                <span class="flex items-center gap-1.5"><x-heroicon-o-credit-card class="h-4 w-4" /> &euro;{{ number_format($courseEdition->monthly_cost, 2) }}/mese &middot; &euro;{{ number_format($courseEdition->annual_cost, 2) }}/anno</span>
            </div>
            @if ($courseEdition->instructors->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($courseEdition->instructors as $instructor)
                        <x-badge>{{ $instructor->name }}</x-badge>
                    @endforeach
                </div>
            @endif
        </x-card>

        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Iscritti ({{ $courseEdition->enrollments->count() }})</h2>
            <x-card class="divide-y divide-gray-100 p-0">
                @forelse ($courseEdition->enrollments as $enrollment)
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <div>
                            <p class="font-medium text-gray-900">{{ $enrollment->user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $enrollment->user->email }}</p>
                        </div>
                        <x-badge :color="$enrollment->status === 'active' ? 'green' : 'gray'">{{ $enrollment->status }}</x-badge>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-500">Nessun iscritto.</p>
                @endforelse
            </x-card>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Lezioni</h2>
            <x-card class="divide-y divide-gray-100 p-0">
                @forelse ($courseEdition->lessons as $lesson)
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <x-heroicon-o-calendar-days class="h-4 w-4 text-gray-400" />
                            {{ $lesson->date->translatedFormat('d M Y') }}
                            @if ($lesson->date->isFuture())
                                <x-badge color="amber">in programma</x-badge>
                            @endif
                        </div>

                        @if ($canManageAttendance)
                            <a href="{{ route('editions.attendance.edit', [$courseEdition, $lesson]) }}"
                               class="flex items-center gap-1.5 text-sm font-medium text-gray-900 hover:underline">
                                <x-heroicon-o-clipboard-document-check class="h-4 w-4" />
                                Registra presenze
                            </a>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-500">Nessuna lezione programmata.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
