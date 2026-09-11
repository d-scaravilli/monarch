@php
    $accentColor = $currentModule->color ?? 'orange';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
    $weekTrend = $weekAttendanceRate !== null
        ? ['direction' => 'neutral', 'label' => $weekAttendanceRate.'% presenze']
        : null;
@endphp

<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="space-y-6">
        {{-- Stat row --}}
        <div class="grid gap-4 grid-cols-2">
            <x-metric-card icon="users" label="Iscritti attivi" :value="$activeEnrollments" :color="$accentColor" />
            <x-metric-card icon="calendar-days" label="Lezioni questa settimana" :value="$lessonsThisWeek" :color="$accentColor" :trend="$weekTrend" />
        </div>

        {{-- Chart + top presence --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <x-card class="lg:col-span-2">
                <x-section-header>Andamento presenze</x-section-header>
                <div
                    x-data="{
                        async init() {
                            const ApexCharts = await window.loadApexCharts();
                            const isDark = document.documentElement.classList.contains('dark');
                            const chart = new ApexCharts(this.$refs.chart, {
                                chart: { type: 'area', height: 260, toolbar: { show: false }, fontFamily: 'inherit', foreColor: isDark ? '#9ca3af' : '#6b7280' },
                                series: [{ name: 'Presenze', data: {{ Illuminate\Support\Js::from(array_values($weeklyAttendanceTrend)) }} }],
                                xaxis: { categories: {{ Illuminate\Support\Js::from(array_keys($weeklyAttendanceTrend)) }}, axisBorder: { show: false }, axisTicks: { show: false } },
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

            <x-card>
                <x-section-header>Iscritti con più presenze</x-section-header>
                <div class="divide-y divide-gray-100 dark:divide-white/10 -mx-5">
                    @forelse ($topPresent as $i => $row)
                        <a href="{{ route('members.show', $row->user) }}" class="flex items-center gap-3 px-5 py-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                         {{ $i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">
                                {{ $i + 1 }}
                            </span>
                            <p class="flex-1 min-w-0 font-medium text-gray-900 dark:text-gray-100 truncate">{{ $row->user->name }}</p>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 shrink-0">{{ $row->count }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessuna presenza registrata.</p>
                    @endforelse
                </div>
            </x-card>
        </div>

        {{-- Top absences + expiring renewals + upcoming lessons --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <x-card>
                <x-section-header>Iscritti con più assenze</x-section-header>
                <div class="divide-y divide-gray-100 dark:divide-white/10 -mx-5">
                    @forelse ($topAbsent as $i => $row)
                        <a href="{{ route('members.show', $row->user) }}" class="flex items-center gap-3 px-5 py-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                                {{ $i + 1 }}
                            </span>
                            <p class="flex-1 min-w-0 font-medium text-gray-900 dark:text-gray-100 truncate">{{ $row->user->name }}</p>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 shrink-0">{{ $row->count }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessuna assenza registrata.</p>
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <x-section-header>Iscrizioni in scadenza (30 giorni)</x-section-header>
                <div class="divide-y divide-gray-100 dark:divide-white/10 -mx-5 max-h-96 overflow-y-auto">
                    @forelse ($expiringEnrollments as $enrollment)
                        <a href="{{ route('members.show', $enrollment->user) }}" class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $enrollment->user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $enrollment->course->discipline->name }} &middot; {{ $enrollment->billing_frequency === 'monthly' ? 'mensile' : 'annuale' }}</p>
                            </div>
                            <x-badge :color="$enrollment->days_until_renewal <= 7 ? 'amber' : 'gray'" class="shrink-0">
                                {{ $enrollment->days_until_renewal === 0 ? 'oggi' : $enrollment->days_until_renewal.'g' }}
                            </x-badge>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessuna scadenza nei prossimi 30 giorni.</p>
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <x-section-header>Prossime lezioni ed eventi (30 giorni)</x-section-header>
                <div class="divide-y divide-gray-100 dark:divide-white/10 -mx-5 max-h-96 overflow-y-auto">
                    @forelse ($upcomingLessons as $lesson)
                        <a href="{{ route('courses.lessons.attendance.edit', [$lesson->course, $lesson]) }}" class="flex items-center justify-between px-5 py-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $lesson->course->discipline->name }}</p>
                                <p class="text-xs text-gray-400">{{ $lesson->course->room->name }}</p>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $lesson->date->translatedFormat('D d M') }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessuna lezione in programma.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
