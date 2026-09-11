@php
    $canManageAttendance = auth()->user()->can('manageAttendance', $course);
    $accentColor = $currentModule->color ?? 'gray';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
    $accent = \App\Support\ModuleTheme::classes($accentColor);
@endphp

<x-app-layout>
    <x-slot name="header">{{ $course->discipline->name }}</x-slot>

    <div class="space-y-6" x-data="{ openEnroll: false, openPayment: false }">
        @if ($course->trashed())
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-700 dark:text-amber-400 ring-1 ring-amber-100 dark:ring-amber-500/20">
                Questo corso è stato eliminato. Stai consultando lo storico.
            </div>
        @endif

        @if ($canManage && ! $course->trashed())
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="openPayment = true; $refs.paymentsSection.scrollIntoView({ behavior: 'smooth' })"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <x-heroicon-o-plus class="h-4 w-4" /> Registra pagamento
                </button>
                <button type="button" @click="openEnroll = true; $refs.enrollSection.scrollIntoView({ behavior: 'smooth' })"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi iscritto
                </button>
                <a href="{{ route('lessons.generate', ['course_id' => $course->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <x-heroicon-o-square-3-stack-3d class="h-4 w-4" /> Genera lezioni
                </a>
                <a href="{{ route('lessons.create', ['course_id' => $course->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <x-heroicon-o-plus class="h-4 w-4" /> Nuova lezione singola
                </a>
            </div>
        @endif

        <x-card class="overflow-hidden">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $accent['badge'] }} text-white">
                        <x-heroicon-o-sparkles class="h-6 w-6" />
                    </span>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $course->discipline->name }}</p>
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

            @if ($course->description)
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ $course->description }}</p>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-white/10 pt-4">
                <span class="flex items-center gap-1.5"><x-heroicon-o-credit-card class="h-4 w-4" /> €{{ number_format($course->monthly_cost, 2) }}/mese &middot; €{{ number_format($course->annual_cost, 2) }}/anno</span>
            </div>

            @if ($course->instructors->isNotEmpty() || $course->schedules->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($course->instructors as $instructor)
                        <x-badge>{{ $instructor->name }}</x-badge>
                    @endforeach
                    @foreach ($course->schedules->sortBy('weekday') as $schedule)
                        <x-badge color="amber">{{ $schedule->weekdayLabel() }} {{ substr($schedule->start_time, 0, 5) }}-{{ substr($schedule->end_time, 0, 5) }}</x-badge>
                    @endforeach
                </div>
            @endif
        </x-card>

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
        </div>

        <div>
            <x-section-header>Iscritti ({{ $course->enrollments->count() }})</x-section-header>
            <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                @forelse ($course->enrollments as $enrollment)
                    <x-swipe-row :action="route('enrollments.destroy', $enrollment)">
                        <a href="{{ route('members.show', $enrollment->user) }}" class="flex items-center justify-between px-5 py-3.5">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $enrollment->user->email }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($enrollment->discount > 0)
                                    <x-badge color="amber">-€{{ number_format($enrollment->discount, 2) }}</x-badge>
                                @endif
                                <x-badge :color="$enrollment->status === 'active' ? 'green' : 'gray'">{{ $enrollment->status }}</x-badge>
                            </div>
                        </a>
                    </x-swipe-row>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-500">Nessun iscritto.</p>
                @endforelse
            </x-card>

            @if ($canManage && ! $course->trashed())
                <div class="mt-3" x-ref="enrollSection">
                    <button type="button" @click="openEnroll = !openEnroll" class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-1">
                        <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi iscritto
                    </button>
                    <x-card x-show="openEnroll" x-cloak class="mt-3">
                        <form method="POST" action="{{ route('courses.enrollments.store', $course) }}" class="flex flex-wrap items-end gap-3">
                            @csrf
                            <div class="flex-1 min-w-[10rem] space-y-1.5">
                                <x-input-label value="Iscritto" />
                                <select name="user_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                    @forelse ($availableMembers as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @empty
                                        <option value="">Nessun iscritto disponibile</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="w-28 space-y-1.5">
                                <x-input-label value="Sconto €" />
                                <x-text-input type="number" step="0.01" min="0" name="discount" value="0" class="w-full" />
                            </div>
                            <div class="w-32 space-y-1.5">
                                <x-input-label value="Tipo" />
                                <select name="billing_frequency" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                    <option value="annual">Annuale</option>
                                    <option value="monthly">Mensile</option>
                                </select>
                            </div>
                            <x-primary-button>Iscrivi</x-primary-button>
                        </form>
                    </x-card>
                </div>
            @endif
        </div>

        <div>
            <x-section-header>Lezioni</x-section-header>
            <livewire:lessons-table :course-id="$course->id" />
        </div>

        @if ($canManage)
            <div x-ref="paymentsSection">
                <x-section-header>Pagamenti</x-section-header>
                <livewire:course-payments-table :course-id="$course->id" />

                @if ($course->enrollments->isNotEmpty() && ! $course->trashed())
                    <div class="mt-3">
                        <button type="button" @click="openPayment = !openPayment" class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-1">
                            <x-heroicon-o-plus class="h-4 w-4" /> Registra pagamento
                        </button>
                        <x-card x-show="openPayment" x-cloak class="mt-3">
                            <form method="POST" :action="url" x-data="{ url: '' }" class="flex flex-wrap items-end gap-3">
                                @csrf
                                <div class="flex-1 min-w-[10rem] space-y-1.5">
                                    <x-input-label value="Iscritto" />
                                    <select @change="url = $event.target.value" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                        <option value="">Seleziona...</option>
                                        @foreach ($course->enrollments as $enrollment)
                                            <option value="{{ route('enrollments.payments.store', $enrollment) }}">{{ $enrollment->user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-24 space-y-1.5">
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
                            </form>
                        </x-card>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
