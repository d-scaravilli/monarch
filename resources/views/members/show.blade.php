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
    $payments = $member->enrollments->flatMap(fn ($e) => $e->payments->map(fn ($p) => tap($p, fn ($p) => $p->enrollment = $e)));

    $activeEnrollmentsCount = $member->enrollments->where('status', 'active')->count();
    $attendanceRate = $attendances->isEmpty() ? null : round($attendances->where('present', true)->count() / $attendances->count() * 100);
    $totalPaid = $payments->sum('amount');

    $accentColor = $currentModule->color ?? 'gray';
    $accent = \App\Support\ModuleTheme::classes($accentColor);
    $initials = collect(explode(' ', $member->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
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
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $accent['badge'] }} text-lg font-bold text-white">
                        {{ mb_strtoupper($initials) }}
                    </span>
                    <div>
                        <p class="font-semibold text-lg text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            {{ $member->name }}
                            @if ($recentInjury)
                                <x-badge color="red">Infortunio recente</x-badge>
                            @endif
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                    </div>
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

            <div class="mt-5 grid grid-cols-3 divide-x divide-gray-100 dark:divide-white/10 rounded-xl bg-gray-50 dark:bg-white/5 py-3 text-center">
                <div>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $activeEnrollmentsCount }}</p>
                    <p class="text-xs text-gray-400">iscrizioni attive</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</p>
                    <p class="text-xs text-gray-400">presenze</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100">&euro;{{ number_format($totalPaid, 0) }}</p>
                    <p class="text-xs text-gray-400">totale versato</p>
                </div>
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
                    <a href="{{ route('courses.show', $enrollment->course) }}" class="block px-5 py-3.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->course->discipline->name }} &middot; {{ $enrollment->course->year }}</p>
                                <p class="text-xs text-gray-400">Dal {{ $enrollment->enrollment_date->translatedFormat('d M Y') }}</p>
                            </div>
                            <x-badge :color="$enrollment->status === 'active' ? 'green' : 'gray'">{{ $enrollment->status }}</x-badge>
                        </div>

                        @if ($enrollment->status === 'active')
                            <div class="mt-3">
                                <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                                    <div class="h-full rounded-full {{ $accent['badge'] }}" style="width: {{ $enrollment->renewalProgressPercent() }}%"></div>
                                </div>
                                <p class="mt-1.5 text-xs text-gray-400">
                                    Rinnovo {{ $enrollment->billing_frequency === 'monthly' ? 'mensile' : 'annuale' }}
                                    &middot; {{ $enrollment->daysUntilRenewal() }} giorni al {{ $enrollment->renewalDate()->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        @endif
                    </a>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-500">Nessuna iscrizione.</p>
                @endforelse
            </x-card>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
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

            <div>
                <x-section-header>Pagamenti</x-section-header>
                <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                    @forelse ($payments->sortByDesc('date') as $payment)
                        <div class="flex items-center justify-between px-5 py-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $payment->date->translatedFormat('d M Y') }} &middot; {{ $payment->method }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">&euro;{{ number_format($payment->amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessun pagamento registrato.</p>
                    @endforelse
                </x-card>

                @if (! $member->trashed() && $member->enrollments->isNotEmpty())
                    <div class="mt-3" x-data="{ open: false, url: '' }">
                        <button type="button" @click="open = !open" class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-1">
                            <x-heroicon-o-plus class="h-4 w-4" /> Registra pagamento
                        </button>
                        <x-card x-show="open" x-cloak class="mt-3">
                            <form method="POST" :action="url" class="space-y-3">
                                @csrf
                                <div class="space-y-1.5">
                                    <x-input-label value="Corso" />
                                    <select @change="url = $event.target.value" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                        <option value="">Seleziona...</option>
                                        @foreach ($member->enrollments as $enrollment)
                                            <option value="{{ route('enrollments.payments.store', $enrollment) }}">{{ $enrollment->course->discipline->name }} ({{ $enrollment->course->year }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="w-28 space-y-1.5">
                                        <x-input-label value="Importo €" />
                                        <x-text-input type="number" step="0.01" min="0" name="amount" class="w-full" />
                                    </div>
                                    <div class="w-32 space-y-1.5">
                                        <x-input-label value="Metodo" />
                                        <select name="method" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                            <option value="contanti">Contanti</option>
                                            <option value="bonifico">Bonifico</option>
                                            <option value="carta">Carta</option>
                                        </select>
                                    </div>
                                    <div class="w-36 space-y-1.5">
                                        <x-input-label value="Data" />
                                        <x-text-input type="date" name="date" value="{{ now()->toDateString() }}" class="w-full" />
                                    </div>
                                    <x-primary-button>Registra</x-primary-button>
                                </div>
                            </form>
                        </x-card>
                    </div>
                @endif
            </div>
        </div>

        <div>
            <x-section-header>Note</x-section-header>
            <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                @forelse ($member->notes as $note)
                    <div class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <x-badge :color="$note->type === 'infortunio' ? 'red' : 'gray'">{{ $note->typeLabel() }}</x-badge>
                            <span class="text-xs text-gray-400">
                                {{ $note->created_at->translatedFormat('d M Y') }}
                                &middot; {{ $note->author->name }}
                                @if ($note->lesson?->course?->discipline)
                                    &middot; {{ $note->lesson->course->discipline->name }}
                                @endif
                            </span>
                        </div>
                        <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $note->description }}</p>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-500">Nessuna nota registrata.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
