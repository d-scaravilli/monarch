@php
    $attendances = $member->enrollments->flatMap(fn ($e) => $e->validAttendances()->map(fn ($a) => tap($a, fn ($a) => $a->enrollment = $e)));
    $payments = $member->enrollments->flatMap(fn ($e) => $e->payments->map(fn ($p) => tap($p, fn ($p) => $p->enrollment = $e)));

    $activeEnrollmentsCount = $member->enrollments->where('status', 'active')->count();
    $attendanceRate = $attendances->isEmpty() ? null : round($attendances->where('present', true)->count() / $attendances->count() * 100);
    $totalPaid = $payments->sum('amount');

    $accentColor = $currentModule->color ?? 'gray';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
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

        {{-- Cover + avatar --}}
        <div class="rounded-2xl overflow-hidden bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-100 dark:ring-white/10">
            <div class="h-28 sm:h-36 relative {{ $accent['badge'] }}"
                 style="background-image: linear-gradient(135deg, {{ $accentHex }} 0%, {{ $accentHex }}99 100%);">
                @if (! $member->trashed())
                    <div class="absolute top-3 right-3 flex items-center gap-1">
                        <a href="{{ route('members.edit', $member) }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur hover:bg-white/30">
                            <x-heroicon-o-pencil class="h-4 w-4" />
                        </a>
                        <form method="POST" action="{{ route('members.destroy', $member) }}" onsubmit="return confirm('Eliminare questo iscritto? Lo storico resterà consultabile.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur hover:bg-red-500/60">
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="px-5 sm:px-6 pb-5">
                <div class="-mt-10 sm:-mt-12 flex items-end gap-4">
                    <span class="relative z-10 flex h-20 w-20 sm:h-24 sm:w-24 shrink-0 items-center justify-center rounded-2xl bg-white dark:bg-gray-900 text-2xl font-bold {{ $accent['text'] }} ring-4 ring-white dark:ring-gray-900 shadow-sm">
                        {{ mb_strtoupper($initials) }}
                    </span>
                    <div class="pb-1 min-w-0">
                        <p class="font-bold text-xl text-gray-900 dark:text-gray-100 flex items-center gap-2 truncate">
                            {{ $member->name }}
                            @if ($recentInjury)
                                <x-badge color="red">Infortunio recente</x-badge>
                            @endif
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $member->email }}</p>
                    </div>
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

            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Left column: anagrafica, equipaggiamento, charts --}}
            <div class="space-y-6 lg:col-span-1">
                <div>
                    <x-section-header>Anagrafica</x-section-header>
                    <x-card class="space-y-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">Codice fiscale</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->memberProfile->fiscal_code ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Contatto di emergenza</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->memberProfile->emergency_contact ?? '—' }}</p>
                        </div>
                        @if ($member->memberProfile?->notes)
                            <div>
                                <p class="text-xs text-gray-400">Note</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->memberProfile->notes }}</p>
                            </div>
                        @endif
                    </x-card>
                </div>

                <div>
                    <x-section-header>Equipaggiamento</x-section-header>
                    <x-card class="space-y-3 text-sm">
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 dark:bg-white/5 px-4 py-3">
                            <span class="text-gray-700 dark:text-gray-300">Spada propria</span>
                            <x-badge :color="$member->memberProfile?->owns_sword ? 'green' : 'gray'">{{ $member->memberProfile?->owns_sword ? 'Sì' : 'No' }}</x-badge>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 dark:bg-white/5 px-4 py-3">
                            <span class="text-gray-700 dark:text-gray-300">Maglietta consegnata</span>
                            <x-badge :color="$member->memberProfile?->shirt_given ? 'green' : 'gray'">{{ $member->memberProfile?->shirt_given ? 'Sì' : 'No' }}</x-badge>
                        </div>
                        <div class="rounded-xl bg-gray-50 dark:bg-white/5 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">Attrezzatura in prestito</span>
                                <x-badge :color="$member->memberProfile?->has_borrowed_equipment ? 'amber' : 'gray'">{{ $member->memberProfile?->has_borrowed_equipment ? 'Sì' : 'No' }}</x-badge>
                            </div>
                            @if ($member->memberProfile?->has_borrowed_equipment && $member->memberProfile?->borrowed_equipment_notes)
                                <p class="mt-1.5 text-xs text-gray-500">{{ $member->memberProfile->borrowed_equipment_notes }}</p>
                            @endif
                        </div>
                    </x-card>
                </div>

                <div class="grid grid-cols-2 gap-4 lg:grid-cols-1">
                    <x-card class="flex flex-col items-center">
                        <x-section-header class="self-start">Livello di presenze</x-section-header>
                        <div class="w-full" x-data="{
                            async init() {
                                const ApexCharts = await window.loadApexCharts();
                                const isDark = document.documentElement.classList.contains('dark');
                                const chart = new ApexCharts(this.$refs.gauge, {
                                    chart: { type: 'radialBar', height: 180, fontFamily: 'inherit' },
                                    series: [{{ $attendanceRate ?? 0 }}],
                                    labels: ['Presenze'],
                                    colors: ['{{ $accentHex }}'],
                                    plotOptions: { radialBar: { hollow: { size: '60%' }, dataLabels: { value: { fontSize: '1.2rem', fontWeight: 700, color: isDark ? '#f3f4f6' : '#111827', formatter: (v) => v + '%' } } } },
                                });
                                chart.render();
                            },
                        }">
                            <div x-ref="gauge"></div>
                        </div>
                    </x-card>

                    <x-card class="flex flex-col items-center">
                        <x-section-header class="self-start">Completamento pagamenti</x-section-header>
                        <div class="w-full" x-data="{
                            async init() {
                                const ApexCharts = await window.loadApexCharts();
                                const isDark = document.documentElement.classList.contains('dark');
                                const chart = new ApexCharts(this.$refs.gauge, {
                                    chart: { type: 'radialBar', height: 180, fontFamily: 'inherit' },
                                    series: [{{ $paymentCompletionPercent }}],
                                    labels: ['Pagato'],
                                    colors: ['{{ $accentHex }}'],
                                    plotOptions: { radialBar: { hollow: { size: '60%' }, dataLabels: { value: { fontSize: '1.2rem', fontWeight: 700, color: isDark ? '#f3f4f6' : '#111827', formatter: (v) => v + '%' } } } },
                                });
                                chart.render();
                            },
                        }">
                            <div x-ref="gauge"></div>
                        </div>
                    </x-card>
                </div>
            </div>

            {{-- Right column: iscrizioni, presenze/pagamenti, note --}}
            <div class="space-y-6 lg:col-span-2">
                <div>
                    <x-section-header>Iscrizioni</x-section-header>
                    <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                        @forelse ($member->enrollments as $enrollment)
                            @php $status = $enrollment->balanceStatus(); @endphp
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

                                <div class="mt-3 flex items-center justify-between rounded-xl bg-gray-50 dark:bg-white/5 px-3 py-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Pagato €{{ number_format($enrollment->paidAmount(), 2) }} di €{{ number_format($enrollment->dueAmount(), 2) }}
                                    </span>
                                    @if ($status === 'missing')
                                        <x-badge color="amber">Mancano €{{ number_format(abs($enrollment->balance()), 2) }}</x-badge>
                                    @elseif ($status === 'overpaid')
                                        <x-badge color="green">Pagato +€{{ number_format($enrollment->balance(), 2) }}</x-badge>
                                    @else
                                        <x-badge color="green">In pari</x-badge>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-500">Nessuna iscrizione.</p>
                        @endforelse
                    </x-card>
                </div>

                <div>
                    <x-section-header>Presenze</x-section-header>
                    <livewire:member-attendance-table :member-id="$member->id" />
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

                <div>
                    <x-section-header>Documenti</x-section-header>
                    <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                        @forelse ($member->documents as $document)
                            <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $document->typeLabel() }}</p>
                                    <p class="text-xs text-gray-400">
                                        Caricato il {{ $document->uploaded_at->translatedFormat('d M Y') }}
                                        @if ($document->expiry_date)
                                            &middot; Scade il {{ $document->expiry_date->translatedFormat('d M Y') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($document->expiry_date)
                                        @if ($document->isExpired())
                                            <x-badge color="red">Scaduto</x-badge>
                                        @elseif ($document->isExpiringSoon())
                                            <x-badge color="amber">In scadenza</x-badge>
                                        @else
                                            <x-badge color="green">Valido</x-badge>
                                        @endif
                                    @endif
                                    @if ($document->fileUrl())
                                        <a href="{{ $document->fileUrl() }}" target="_blank" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                        </a>
                                    @endif
                                    @if (! $member->trashed())
                                        <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Eliminare questo documento?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                                                <x-heroicon-o-trash class="h-4 w-4" />
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-500">Nessun documento caricato.</p>
                        @endforelse
                    </x-card>

                    @if (! $member->trashed())
                        <div class="mt-3" x-data="{ open: false, type: 'certificato_medico' }">
                            <button type="button" @click="open = !open" class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-1">
                                <x-heroicon-o-plus class="h-4 w-4" /> Carica documento
                            </button>
                            <x-card x-show="open" x-cloak class="mt-3">
                                <form method="POST" action="{{ route('members.documents.store', $member) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <div class="grid sm:grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <x-input-label value="Tipo" />
                                            <select name="type" x-model="type" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                                @foreach (\App\Models\Document::TYPES as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-1.5" x-show="type === 'altro'" x-cloak>
                                            <x-input-label value="Etichetta" />
                                            <x-text-input name="custom_label" class="w-full" placeholder="Es. Referto medico" />
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <x-input-label value="File" />
                                        <input type="file" name="file" required
                                               class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-xl file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm dark:file:bg-white/10 dark:file:text-gray-200" />
                                    </div>
                                    <div class="grid sm:grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <x-input-label value="Data caricamento" />
                                            <x-text-input type="date" name="uploaded_at" value="{{ now()->toDateString() }}" class="w-full" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <x-input-label value="Scadenza (facoltativa)" />
                                            <x-text-input type="date" name="expiry_date" class="w-full" />
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <x-primary-button>Carica</x-primary-button>
                                    </div>
                                </form>
                            </x-card>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
