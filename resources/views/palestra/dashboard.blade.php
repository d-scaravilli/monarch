@php
    $accentColor = $currentModule->color ?? 'orange';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
@endphp

<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="space-y-6">
        {{-- Stat row --}}
        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            <x-metric-card icon="users" label="Iscritti attivi" :value="$activeEnrollments" :color="$accentColor" />

            @if ($isAdmin)
                <x-metric-card icon="banknotes" label="Incassi del mese" value="€{{ number_format($revenueThisMonth, 0) }}" :color="$accentColor" :trend="$revenueTrend" />
            @endif

            <x-metric-card
                icon="clipboard-document-check"
                label="Presenze (4 sett.)"
                :value="$avgAttendanceRate !== null ? $avgAttendanceRate.'%' : '—'"
                :color="$accentColor"
            />

            <x-metric-card icon="calendar-days" label="Lezioni di oggi" :value="$lessonsToday" :color="$accentColor" />
        </div>

        {{-- Chart + top courses --}}
        <div class="grid gap-4 lg:grid-cols-3">
            @if ($isAdmin)
                <x-card class="lg:col-span-2">
                    <x-section-header>Incassi mensili</x-section-header>
                    <div
                        x-data="{
                            async init() {
                                const ApexCharts = await window.loadApexCharts();
                                const isDark = document.documentElement.classList.contains('dark');
                                const chart = new ApexCharts(this.$refs.chart, {
                                    chart: { type: 'area', height: 260, toolbar: { show: false }, fontFamily: 'inherit', foreColor: isDark ? '#9ca3af' : '#6b7280' },
                                    series: [{ name: 'Incassi', data: {{ Illuminate\Support\Js::from(array_values($monthlyRevenue)) }} }],
                                    xaxis: { categories: {{ Illuminate\Support\Js::from(array_keys($monthlyRevenue)) }}, axisBorder: { show: false }, axisTicks: { show: false } },
                                    yaxis: { labels: { formatter: (v) => '&euro;' + Math.round(v) } },
                                    colors: ['{{ $accentHex }}'],
                                    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0 } },
                                    dataLabels: { enabled: false },
                                    stroke: { curve: 'smooth', width: 2.5 },
                                    grid: { borderColor: isDark ? 'rgba(255,255,255,0.08)' : 'rgba(148,163,184,0.15)', strokeDashArray: 3 },
                                    tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: (v) => '&euro;' + v.toFixed(2) } },
                                });
                                chart.render();
                            },
                        }"
                    >
                        <div x-ref="chart"></div>
                    </div>
                </x-card>
            @endif

            <x-card class="{{ $isAdmin ? '' : 'lg:col-span-3' }}">
                <x-section-header>Corsi con più iscritti</x-section-header>
                <div class="divide-y divide-gray-100 dark:divide-white/10 -mx-5">
                    @forelse ($topCourses as $i => $course)
                        <a href="{{ route('courses.show', $course) }}" class="flex items-center gap-3 px-5 py-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                         {{ $i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">
                                {{ $i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $course->discipline->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $course->room->name }} &middot; {{ $course->year }}</p>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 shrink-0">{{ $course->enrollments_count }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessun corso disponibile.</p>
                    @endforelse
                </div>
            </x-card>
        </div>

        {{-- Upcoming lessons + expiring certificates --}}
        <div class="grid gap-4 {{ $isAdmin ? 'lg:grid-cols-2' : '' }}">
            <x-card>
                <x-section-header>Prossime lezioni (7 giorni)</x-section-header>
                <div class="divide-y divide-gray-100 dark:divide-white/10 -mx-5">
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

            @if ($isAdmin)
                <x-card>
                    <x-section-header>Certificati in scadenza (30 giorni)</x-section-header>
                    <div class="divide-y divide-gray-100 dark:divide-white/10 -mx-5">
                        @forelse ($expiringCertificates as $certificate)
                            <a href="{{ route('members.show', $certificate->user) }}" class="flex items-center justify-between px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-exclamation-triangle class="h-4 w-4 text-amber-500 shrink-0" />
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $certificate->user->name }}</p>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $certificate->expiry_date->translatedFormat('d M Y') }}</span>
                            </a>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-500">Nessun certificato in scadenza.</p>
                        @endforelse
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
