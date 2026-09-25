@php
    // Corsi only: an evento's presences stay inside the evento itself and never enter this overall rate.
    $attendances = $member->enrollments->reject(fn ($e) => $e->course->isEvento())->flatMap(fn ($e) => $e->validAttendances()->map(fn ($a) => tap($a, fn ($a) => $a->enrollment = $e)));

    $activeEnrollmentsCount = $member->enrollments->where('status', 'active')->count();
    $attendanceRate = $attendances->isEmpty() ? null : round($attendances->where('present', true)->count() / $attendances->count() * 100);
    $totalPaid = $member->enrollments->sum(fn ($e) => $e->paidAmount());

    $accentColor = $currentModule->color ?? 'gray';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
    $accent = \App\Support\ModuleTheme::classes($accentColor);
    $coverTransparent = $currentModule?->memberCoverIsTransparent() ?? false;
    $coverImage = $coverTransparent ? null : $currentModule?->memberCoverImageUrl();
    $initials = collect(explode(' ', $member->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<x-app-layout>
    <x-slot name="header">La mia area</x-slot>

    <div class="space-y-6" x-data="{ tab: 'iscrizioni' }">
        {{-- Cover + avatar: identity stays visible above the tabs, always --}}
        <div class="rounded-2xl overflow-hidden bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-100 dark:ring-white/10">
            <div class="h-28 sm:h-36 relative {{ $coverTransparent ? '' : ($coverImage ? '' : $accent['badge']) }}"
                 @unless ($coverTransparent)
                     style="background-image: {{ $coverImage ? "linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('{$coverImage}')" : "linear-gradient(135deg, {$accentHex} 0%, {$accentHex}99 100%)" }}; background-size: cover; background-position: center;"
                 @endunless>
            </div>

            <div class="relative px-5 sm:px-6 pb-5">
                <span class="absolute -top-10 sm:-top-12 left-5 sm:left-6 z-10 flex h-20 w-20 sm:h-24 sm:w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white dark:bg-gray-900 text-2xl font-bold {{ $accent['text'] }} ring-4 ring-white dark:ring-gray-900 shadow-sm">
                    @if ($member->avatarUrl())
                        <img src="{{ $member->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                    @else
                        {{ mb_strtoupper($initials) }}
                    @endif
                </span>
                <div class="pt-2 pl-24 sm:pl-28 min-w-0">
                    <p class="font-bold text-xl text-gray-900 dark:text-gray-100 flex items-center gap-2 truncate">
                        {{ $member->name }}
                        @if ($recentInjury)
                            <x-badge color="red">Infortunio recente</x-badge>
                        @endif
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $member->email }}</p>
                </div>

                <div class="mt-5 grid grid-cols-3 divide-x divide-gray-100 dark:divide-white/10 rounded-xl bg-gray-50 dark:bg-white/5 py-3 text-center">
                    <div>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $activeEnrollmentsCount }}</p>
                        <p class="text-xs text-gray-400">iscrizioni attive</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</p>
                        <p class="text-xs text-gray-400">presenze corsi</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">&euro;{{ number_format($totalPaid, 0) }}</p>
                        <p class="text-xs text-gray-400">totale versato</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex w-full gap-1 rounded-xl bg-gray-100 dark:bg-white/5 p-1 text-sm font-medium">
            <button type="button" @click="tab = 'iscrizioni'" class="flex-1 min-w-0 truncate rounded-lg px-1 py-2 text-center transition" :class="tab === 'iscrizioni' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Iscrizioni</button>
            <button type="button" @click="tab = 'pagamenti'" class="flex-1 min-w-0 truncate rounded-lg px-1 py-2 text-center transition" :class="tab === 'pagamenti' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Pagamenti</button>
            <button type="button" @click="tab = 'progressi'" class="flex-1 min-w-0 truncate rounded-lg px-1 py-2 text-center transition" :class="tab === 'progressi' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Progressi</button>
            <button type="button" @click="tab = 'anagrafica'" class="flex-1 min-w-0 truncate rounded-lg px-1 py-2 text-center transition" :class="tab === 'anagrafica' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Anagrafica</button>
        </div>

        {{-- Tab: Anagrafica — personal data, documents, equipment --}}
        <div x-show="tab === 'anagrafica'" x-cloak class="grid gap-6 lg:grid-cols-2 items-start">
            <div class="space-y-6 min-w-0">
                <div>
                    <x-section-header>Anagrafica</x-section-header>
                    <x-card class="space-y-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">Codice fiscale</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->memberProfile?->fiscal_code ?? '—' }}</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-gray-400">Contatto telefonico</p>
                                <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'edit-phone')"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <x-heroicon-o-pencil class="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->memberProfile?->phone ?? '—' }}</p>
                        </div>
                        @if ($member->memberProfile?->notes)
                            <div>
                                <p class="text-xs text-gray-400">Note</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->memberProfile->notes }}</p>
                            </div>
                        @endif

                        <div class="border-t border-gray-100 dark:border-white/10 pt-4">
                            <p class="text-xs text-gray-400 mb-2">Documenti</p>
                            <div class="-mx-5 max-h-72 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10 sm:max-h-none sm:overflow-visible">
                                @forelse ($member->documents as $document)
                                    <div class="flex items-center justify-between gap-3 px-5 py-3">
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
                                        </div>
                                    </div>
                                @empty
                                    <p class="px-5 py-3 text-sm text-gray-500">Nessun documento caricato.</p>
                                @endforelse
                            </div>

                            <div class="mt-3">
                                <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'upload-document')"
                                        class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-1">
                                    <x-heroicon-o-plus class="h-4 w-4" /> Carica documento
                                </button>
                            </div>
                        </div>
                    </x-card>

                    <x-modal name="edit-phone" max-width="sm">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Contatto telefonico</h2>

                            <form method="POST" action="{{ route('member.area.phone.update') }}" class="mt-5 space-y-4">
                                @csrf @method('PATCH')
                                <div class="space-y-1.5">
                                    <x-input-label for="phone" value="Telefono" />
                                    <x-text-input id="phone" type="tel" name="phone" value="{{ old('phone', $member->memberProfile?->phone) }}" class="w-full" />
                                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                                </div>
                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                                    <x-primary-button>Salva</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </x-modal>

                    <x-modal name="upload-document" max-width="lg">
                        <div class="p-6" x-data="{ type: 'certificato_medico' }">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Carica documento</h2>

                            <form method="POST" action="{{ route('members.documents.store', $member) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                                @csrf
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div class="space-y-1.5">
                                        <x-input-label value="Tipo" />
                                        <select name="type" x-model="type" class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
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
                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                                    <x-primary-button>Carica</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </x-modal>
                </div>
            </div>

            <div class="space-y-6 min-w-0">
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
                        <p class="text-xs text-gray-400">Gestito solo dall'amministrazione.</p>
                    </x-card>
                </div>
            </div>
        </div>

        {{-- Tab: Iscrizioni — enrollments and attendance --}}
        <div x-show="tab === 'iscrizioni'" x-cloak class="space-y-6">
            <div>
                <x-section-header>Iscrizioni</x-section-header>
                <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                    @forelse ($member->enrollments as $enrollment)
                        <a href="{{ route('courses.show', $enrollment->course) }}" class="block px-5 py-3.5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->course->displayName() }} &middot; {{ $enrollment->course->year }}</p>
                                    <p class="text-xs text-gray-400">Dal {{ $enrollment->enrollment_date->translatedFormat('d M Y') }}</p>
                                </div>
                                <x-badge :color="$enrollment->status === 'active' ? 'green' : 'gray'">{{ $enrollment->status }}</x-badge>
                            </div>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessuna iscrizione.</p>
                    @endforelse
                </x-card>
            </div>

            <div>
                <x-section-header>Lezioni dei corsi</x-section-header>
                <livewire:member-attendance-table :member-id="$member->id" type="corso" />
            </div>

            @if ($member->enrollments->contains(fn ($enrollment) => $enrollment->course->isEvento()))
                <div>
                    <x-section-header>Lezioni degli eventi</x-section-header>
                    <livewire:member-attendance-table :member-id="$member->id" type="evento" />
                </div>
            @endif
        </div>

        {{-- Tab: Pagamenti — completion gauge and transactions --}}
        <div x-show="tab === 'pagamenti'" x-cloak class="grid gap-6 lg:grid-cols-3 items-start">
            <x-card class="lg:col-span-1 flex flex-col items-center">
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

            <div class="lg:col-span-2 min-w-0">
                <x-section-header>Pagamenti</x-section-header>
                <x-card class="max-h-96 overflow-y-auto p-0 divide-y divide-gray-100 dark:divide-white/10 sm:max-h-none sm:overflow-visible" x-data="{ open: null }">
                    @forelse ($member->enrollments as $enrollment)
                        @php $paymentStatus = $enrollment->balanceStatus(); @endphp
                        <div>
                            <button type="button" @click="open = open === {{ $enrollment->id }} ? null : {{ $enrollment->id }}"
                                    class="w-full flex items-center justify-between gap-3 px-5 py-3.5 text-left">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $enrollment->course->displayName() }} &middot; {{ $enrollment->course->year }}</p>
                                    <p class="text-xs text-gray-400">Pagato €{{ number_format($enrollment->paidAmount(), 2) }} di €{{ number_format($enrollment->dueAmount(), 2) }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($paymentStatus === 'missing')
                                        <x-badge color="amber">Mancano €{{ number_format(abs($enrollment->balance()), 2) }}</x-badge>
                                    @elseif ($paymentStatus === 'overpaid')
                                        <x-badge color="green">+€{{ number_format($enrollment->balance(), 2) }}</x-badge>
                                    @else
                                        <x-badge color="green">In pari</x-badge>
                                    @endif
                                    <span class="transition-transform" :class="open === {{ $enrollment->id }} ? 'rotate-90' : ''">
                                        <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-300" />
                                    </span>
                                </div>
                            </button>
                            <div x-show="open === {{ $enrollment->id }}" x-cloak class="px-5 pb-4 space-y-2">
                                @forelse ($enrollment->payments->sortByDesc('date') as $payment)
                                    <div class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">{{ $payment->date->translatedFormat('d M Y') }} &middot; {{ $payment->method }}</span>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">€{{ number_format($payment->amount, 2) }}</span>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">Nessun pagamento registrato per questo corso.</p>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessuna iscrizione.</p>
                    @endforelse
                </x-card>
            </div>
        </div>

        {{-- Tab: Progressi — per enrollment, goals path (left) and its unlinked notes (right), same component as the dedicated Progressi page. Read-only here: canManage is always false, a member never edits their own goals/notes. --}}
        <div x-show="tab === 'progressi'" x-cloak class="max-w-5xl space-y-8">
            @foreach ($member->enrollments as $enrollment)
                <x-enrollment-goals :enrollment="$enrollment" :notes="$member->notes" :can-manage="false" />
            @endforeach
        </div>
    </div>
</x-app-layout>
