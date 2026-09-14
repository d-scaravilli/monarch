@php
    $canManageAttendance = auth()->user()->can('manageAttendance', $course);
    $accentColor = $currentModule->color ?? 'gray';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
    $accent = \App\Support\ModuleTheme::classes($accentColor);
@endphp

<x-app-layout>
    <x-slot name="header">{{ $course->discipline->name }}</x-slot>

    <div class="space-y-6" x-data="{ showPayments: false }">
        @if ($course->trashed())
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-700 dark:text-amber-400 ring-1 ring-amber-100 dark:ring-amber-500/20">
                Questo corso è stato eliminato. Stai consultando lo storico.
            </div>
        @endif

        @if ($canManage && ! $course->trashed())
            <x-card class="!p-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'register-payment')"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
                        <x-heroicon-o-banknotes class="h-4 w-4" /> Registra pagamento
                    </button>
                    <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'add-enrollment')"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
                        <x-heroicon-o-user-plus class="h-4 w-4" /> Aggiungi iscritto
                    </button>

                    <div class="hidden sm:block w-px h-6 bg-gray-200 dark:bg-white/10"></div>

                    @if (! $course->isEvento())
                        <a href="{{ route('lessons.generate', ['course_id' => $course->id]) }}"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                            <x-heroicon-o-square-3-stack-3d class="h-4 w-4" /> Genera lezioni
                        </a>
                    @endif
                    <a href="{{ route('lessons.create', ['course_id' => $course->id]) }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                        <x-heroicon-o-plus class="h-4 w-4" /> Nuova lezione
                    </a>
                </div>
            </x-card>
        @endif

        <x-card class="overflow-hidden">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $accent['badge'] }} text-white">
                        <x-heroicon-o-sparkles class="h-6 w-6" />
                    </span>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            {{ $course->discipline->name }}
                            <x-badge :color="$course->isEvento() ? 'purple' : 'gray'">{{ $course->isEvento() ? 'Evento' : 'Corso' }}</x-badge>
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $course->room->name }} &middot; {{ $course->year }}</p>
                    </div>
                </div>

                @if ($canManage && ! $course->trashed())
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('courses.edit', $course) }}" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                            <x-heroicon-o-pencil class="h-4 w-4" />
                        </a>
                        <form method="POST" action="{{ route('courses.destroy', $course) }}" onsubmit="return confirm('Eliminare questo corso?{{ $course->enrollments->count() || $course->lessons->count() ? ' Ha '.$course->enrollments->count().' iscritti e '.$course->lessons->count().' lezioni collegate: i dati restano consultabili nello storico.' : '' }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            @unless ($course->isEvento())
                @if ($course->description)
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ $course->description }}</p>
                @endif

                @if ($canManage)
                    <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-white/10 pt-4">
                        <span class="flex items-center gap-1.5">
                            <x-heroicon-o-credit-card class="h-4 w-4" /> €{{ number_format($course->monthly_cost, 2) }}/mese &middot; €{{ number_format($course->annual_cost, 2) }}/anno
                            @if ($course->enrollment_cost)
                                &middot; €{{ number_format($course->enrollment_cost, 2) }} iscrizione
                            @endif
                        </span>
                    </div>
                @endif

                <div class="mt-4 grid gap-4 sm:grid-cols-3 border-t border-gray-100 dark:border-white/10 pt-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['soft'] }}">
                            <x-heroicon-o-map-pin class="h-5 w-5 {{ $accent['text'] }}" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Sala</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $course->room->name }}</p>
                        </div>
                    </div>

                    @if ($course->instructors->isNotEmpty())
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['soft'] }}">
                                <x-heroicon-o-user class="h-5 w-5 {{ $accent['text'] }}" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $course->instructors->count() > 1 ? 'Istruttori' : 'Istruttore' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $course->instructors->pluck('name')->join(', ') }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($course->schedules->isNotEmpty())
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['soft'] }}">
                                <x-heroicon-o-clock class="h-5 w-5 {{ $accent['text'] }}" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Orari</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    @foreach ($course->schedules->sortBy('weekday') as $schedule)
                                        {{ $schedule->weekdayLabel() }} {{ substr($schedule->start_time, 0, 5) }}-{{ substr($schedule->end_time, 0, 5) }}{{ ! $loop->last ? ', ' : '' }}
                                    @endforeach
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @endunless
        </x-card>

        @if ($course->isEvento())
            {{-- Evento: clean, description-led layout instead of the
                 recurring-course charts/lessons-table pair below. --}}
            <x-card class="px-6 py-8 sm:px-10 sm:py-10">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">Descrizione evento</h2>
                @if ($course->description)
                    <p class="text-base leading-relaxed text-gray-600 dark:text-gray-400 max-w-3xl whitespace-pre-line">{{ $course->description }}</p>
                @else
                    <p class="text-sm text-gray-400">Nessuna descrizione.</p>
                @endif

                <div class="mt-8 grid gap-6 sm:grid-cols-{{ $canManage ? 3 : 2 }} border-t border-gray-100 dark:border-white/10 pt-8">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['soft'] }}">
                            <x-heroicon-o-calendar-days class="h-5 w-5 {{ $accent['text'] }}" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Quando</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $course->year }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['soft'] }}">
                            <x-heroicon-o-map-pin class="h-5 w-5 {{ $accent['text'] }}" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Dove</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $course->room->name }}</p>
                        </div>
                    </div>
                    @if ($canManage)
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['soft'] }}">
                                <x-heroicon-o-credit-card class="h-5 w-5 {{ $accent['text'] }}" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Costo</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $course->enrollment_cost ? '€'.number_format($course->enrollment_cost, 2) : 'Gratuito' }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($course->instructors->isNotEmpty())
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($course->instructors as $instructor)
                            <x-badge>{{ $instructor->name }}</x-badge>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-card class="flex flex-col items-center justify-center">
                    <x-section-header class="self-start">Riempimento posti</x-section-header>
                    <div
                        class="w-full"
                        x-data="{
                            async init() {
                                const ApexCharts = await window.loadApexCharts();
                                const isDark = document.documentElement.classList.contains('dark');
                                const chart = new ApexCharts(this.$refs.gauge, {
                                    chart: { type: 'radialBar', height: 220, fontFamily: 'inherit' },
                                    series: [{{ $fillPercent }}],
                                    labels: ['Posti occupati'],
                                    colors: ['{{ $accentHex }}'],
                                    plotOptions: { radialBar: { hollow: { size: '60%' }, dataLabels: { value: { fontSize: '1.5rem', fontWeight: 700, color: isDark ? '#f3f4f6' : '#111827', formatter: (v) => v + '%' } } } },
                                });
                                chart.render();
                            },
                        }"
                    >
                        <div x-ref="gauge"></div>
                    </div>
                    <p class="text-xs text-gray-400 -mt-2">{{ $course->enrollments->count() }}/{{ $course->room->capacity }} posti sala</p>
                </x-card>

                <div>
                    <x-section-header>Giorni evento</x-section-header>
                    <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                        @forelse ($course->lessons as $lesson)
                            <a href="{{ route('courses.lessons.attendance.edit', [$course, $lesson]) }}" class="flex items-center justify-between px-5 py-3.5">
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $lesson->date->translatedFormat('l d F Y') }}</span>
                                <span class="flex items-center gap-2">
                                    <x-badge color="green">{{ $lesson->attendances->where('present', true)->count() }} presenti</x-badge>
                                    <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-300" />
                                </span>
                            </a>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-500">Nessuna data configurata.</p>
                        @endforelse
                    </x-card>
                </div>
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-3">
                <x-card class="lg:col-span-2">
                    <x-section-header>Andamento presenze del corso</x-section-header>
                    <div
                        x-data="{
                            async init() {
                                const ApexCharts = await window.loadApexCharts();
                                const isDark = document.documentElement.classList.contains('dark');
                                const chart = new ApexCharts(this.$refs.chart, {
                                    chart: { type: 'area', height: 220, toolbar: { show: false }, fontFamily: 'inherit', foreColor: isDark ? '#9ca3af' : '#6b7280' },
                                    series: [{ name: 'Presenze', data: {{ Illuminate\Support\Js::from(array_values($attendanceTrend)) }} }],
                                    xaxis: { categories: {{ Illuminate\Support\Js::from(array_keys($attendanceTrend)) }}, axisBorder: { show: false }, axisTicks: { show: false } },
                                    yaxis: { min: 0, max: 100, labels: { formatter: (v) => Math.round(v) + '%' } },
                                    colors: ['{{ $accentHex }}'],
                                    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0 } },
                                    dataLabels: { enabled: false },
                                    stroke: { curve: 'smooth', width: 2.5 },
                                    grid: { borderColor: isDark ? 'rgba(255,255,255,0.08)' : 'rgba(148,163,184,0.15)', strokeDashArray: 3 },
                                    tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: (v) => v + '%' } },
                                });
                                chart.render();
                            },
                        }"
                    >
                        <div x-ref="chart"></div>
                    </div>
                </x-card>

                <x-card class="flex flex-col items-center justify-center">
                    <x-section-header class="self-start">Media presenze del corso</x-section-header>
                    <div
                        class="w-full"
                        x-data="{
                            async init() {
                                const ApexCharts = await window.loadApexCharts();
                                const isDark = document.documentElement.classList.contains('dark');
                                const chart = new ApexCharts(this.$refs.gauge, {
                                    chart: { type: 'radialBar', height: 220, fontFamily: 'inherit' },
                                    series: [{{ $averageAttendanceRate ?? 0 }}],
                                    labels: ['Media presenze'],
                                    colors: ['{{ $accentHex }}'],
                                    plotOptions: { radialBar: { hollow: { size: '60%' }, dataLabels: { value: { fontSize: '1.5rem', fontWeight: 700, color: isDark ? '#f3f4f6' : '#111827', formatter: (v) => v + '%' } } } },
                                });
                                chart.render();
                            },
                        }"
                    >
                        <div x-ref="gauge"></div>
                    </div>
                    <p class="text-xs text-gray-400 -mt-2">
                        {{ $averageAttendanceRate !== null ? 'su tutte le lezioni svolte' : 'nessuna lezione svolta finora' }}
                    </p>
                </x-card>
            </div>
        @endif

        <div>
            <x-section-header>Iscritti ({{ $course->enrollments->count() }})</x-section-header>
            <livewire:course-enrollments-table :course-id="$course->id" :accent-color="$accentColor" :compact="$compactRoster" />
        </div>

        @if ($canManage && ! $course->trashed())
            <x-modal name="add-enrollment" max-width="lg">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Aggiungi iscritto</h2>

                    <form method="POST" action="{{ route('courses.enrollments.store', $course) }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <x-input-label value="Iscritto" />
                            <select name="user_id" class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                @forelse ($availableMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}{{ $member->hasRole('instructor') ? ' — istruttore' : '' }}</option>
                                @empty
                                    <option value="">Nessun iscritto disponibile</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <x-input-label value="Sconto €" />
                                <x-text-input type="number" step="0.01" min="0" name="discount" value="0" class="w-full" />
                            </div>
                            <div class="space-y-1.5">
                                <x-input-label value="Tipo" />
                                <select name="billing_frequency" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                    <option value="annual">Annuale</option>
                                    <option value="monthly">Mensile</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                            <x-primary-button>Iscrivi</x-primary-button>
                        </div>
                    </form>
                </div>
            </x-modal>
        @endif

        @unless ($course->isEvento())
            <div>
                <x-section-header>Lezioni</x-section-header>
                <livewire:lessons-table :course-id="$course->id" />
            </div>
        @endunless

        @if ($canManage)
            <div>
                <div class="flex items-center justify-between">
                    <x-section-header class="mb-0">Pagamenti</x-section-header>
                    <button type="button" @click="showPayments = !showPayments"
                            class="flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-300">
                        <x-heroicon-o-eye class="h-4 w-4" x-show="! showPayments" />
                        <x-heroicon-o-eye-slash class="h-4 w-4" x-show="showPayments" x-cloak />
                        <span x-text="showPayments ? 'Nascondi pagamenti' : 'Mostra pagamenti'"></span>
                    </button>
                </div>
                <div x-show="showPayments" x-cloak class="mt-3">
                    <livewire:course-payments-table :course-id="$course->id" />
                </div>
            </div>

            @if ($course->enrollments->isNotEmpty() && ! $course->trashed())
                <x-payment-modal name="register-payment" :enrollments="$course->enrollments" />
            @endif
        @endif
    </div>
</x-app-layout>
