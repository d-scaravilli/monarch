@php
    $certStatus = 'red';
    $certLabel = 'Nessun certificato caricato';

    if ($medicalCertificate) {
        if ($medicalCertificate->expiry_date->isPast()) {
            $certStatus = 'red';
            $certLabel = 'Scaduto il '.$medicalCertificate->expiry_date->translatedFormat('d M Y');
        } elseif ($medicalCertificate->expiry_date->diffInDays(now()) <= 30) {
            $certStatus = 'amber';
            $certLabel = 'In scadenza il '.$medicalCertificate->expiry_date->translatedFormat('d M Y');
        } else {
            $certStatus = 'green';
            $certLabel = 'Valido fino al '.$medicalCertificate->expiry_date->translatedFormat('d M Y');
        }
    }
@endphp

<x-app-layout>
    <x-slot name="header">La mia area</x-slot>

    <div class="space-y-6">
        <x-card>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-identification class="h-5 w-5 text-gray-400" />
                    <p class="font-medium text-gray-900 dark:text-gray-100">Certificato medico</p>
                </div>
                <x-badge :color="$certStatus">{{ $certLabel }}</x-badge>
            </div>
        </x-card>

        @forelse ($enrollments as $enrollment)
            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $enrollment->course->discipline->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $enrollment->course->room->name }} &middot; {{ $enrollment->course->year }}</p>
                    </div>
                    <x-badge :color="$enrollment->status === 'active' ? 'green' : 'gray'">{{ $enrollment->status }}</x-badge>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-section-header>Presenze</x-section-header>
                        <ul class="space-y-1.5 text-sm">
                            @forelse ($enrollment->attendances->sortByDesc(fn ($a) => $a->lesson->date) as $attendance)
                                <li class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $attendance->lesson->date->translatedFormat('d M Y') }}</span>
                                    @if ($attendance->present)
                                        <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" />
                                    @else
                                        <x-heroicon-o-x-circle class="h-4 w-4 text-red-400" />
                                    @endif
                                </li>
                            @empty
                                <li class="text-gray-400">Nessuna presenza registrata.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <x-section-header>Pagamenti</x-section-header>
                        <ul class="space-y-1.5 text-sm">
                            @forelse ($enrollment->payments->sortByDesc('date') as $payment)
                                <li class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $payment->date->translatedFormat('d M Y') }} &middot; {{ $payment->method }}</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">&euro;{{ number_format($payment->amount, 2) }}</span>
                                </li>
                            @empty
                                <li class="text-gray-400">Nessun pagamento registrato.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </x-card>
        @empty
            <x-card class="text-center text-gray-500 py-10">
                Nessuna iscrizione attiva.
            </x-card>
        @endforelse
    </div>
</x-app-layout>
