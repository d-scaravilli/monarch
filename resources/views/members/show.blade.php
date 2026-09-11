@php
    $certStatus = 'red';
    $certLabel = 'Nessun certificato';
    if ($latestCertificate) {
        if ($latestCertificate->expiry_date->isPast()) {
            $certStatus = 'red';
            $certLabel = 'Scaduto il '.$latestCertificate->expiry_date->translatedFormat('d M Y');
        } elseif ($latestCertificate->expiry_date->diffInDays(now()) <= 30) {
            $certStatus = 'amber';
            $certLabel = 'In scadenza il '.$latestCertificate->expiry_date->translatedFormat('d M Y');
        } else {
            $certStatus = 'green';
            $certLabel = 'Valido fino al '.$latestCertificate->expiry_date->translatedFormat('d M Y');
        }
    }

    $attendances = $member->enrollments->flatMap(fn ($e) => $e->attendances->map(fn ($a) => tap($a, fn ($a) => $a->enrollment = $e)));
@endphp

<x-app-layout>
    <x-slot name="header">{{ $member->name }}</x-slot>

    <div class="space-y-6">
        @if ($member->trashed())
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-700 dark:text-amber-400 ring-1 ring-amber-100 dark:ring-amber-500/20">
                Questo iscritto è stato eliminato. Stai consultando lo storico.
            </div>
        @endif

        <x-card>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $member->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                </div>
                @if (! $member->trashed())
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('members.edit', $member) }}" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                            <x-heroicon-o-pencil class="h-4 w-4" />
                        </a>
                        <form method="POST" action="{{ route('members.destroy', $member) }}" onsubmit="return confirm('Eliminare questo iscritto? Lo storico resterà consultabile.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="mt-4 flex items-center justify-between rounded-xl bg-gray-50 dark:bg-white/5 px-4 py-3">
                <span class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <x-heroicon-o-identification class="h-5 w-5 text-gray-400" />
                    Certificato medico
                </span>
                <x-badge :color="$certStatus">{{ $certLabel }}</x-badge>
            </div>
        </x-card>

        <div>
            <x-section-header>Anagrafica</x-section-header>
            <x-card class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Codice fiscale</p>
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->memberProfile->fiscal_code ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Contatto di emergenza</p>
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->memberProfile->emergency_contact ?? '—' }}</p>
                </div>
                @if ($member->memberProfile?->notes)
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400">Note</p>
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->memberProfile->notes }}</p>
                    </div>
                @endif
            </x-card>
        </div>

        <div>
            <x-section-header>Iscrizioni</x-section-header>
            <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                @forelse ($member->enrollments as $enrollment)
                    <a href="{{ route('courses.show', $enrollment->course) }}" class="flex items-center justify-between px-5 py-3.5">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->course->discipline->name }} &middot; {{ $enrollment->course->year }}</p>
                            <p class="text-xs text-gray-400">Dal {{ $enrollment->enrollment_date->translatedFormat('d M Y') }}</p>
                        </div>
                        <x-badge :color="$enrollment->status === 'active' ? 'green' : 'gray'">{{ $enrollment->status }}</x-badge>
                    </a>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-500">Nessuna iscrizione.</p>
                @endforelse
            </x-card>
        </div>

        <div>
            <x-section-header>Presenze</x-section-header>
            <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                @forelse ($attendances->sortByDesc(fn ($a) => $a->lesson->date)->take(15) as $attendance)
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $attendance->lesson->date->translatedFormat('d M Y') }}</span>
                        @if ($attendance->present)
                            <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" />
                        @else
                            <x-heroicon-o-x-circle class="h-4 w-4 text-red-400" />
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-500">Nessuna presenza registrata.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
