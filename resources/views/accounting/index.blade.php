@php
    $accentColor = $currentModule->color ?? 'gray';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
@endphp

<x-app-layout>
    <x-slot name="header">Contabilità</x-slot>

    <div class="space-y-6">
        <x-card>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-year-select :years="$years" :selected="$selectedYear" all-label="" />
            </form>
        </x-card>

        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            <x-metric-card icon="banknotes" label="Totale atteso" value="€{{ number_format($totals['expected'], 2) }}" :color="$accentColor" />
            <x-metric-card icon="check-circle" label="Totale incassato" value="€{{ number_format($totals['collected'], 2) }}" :color="$accentColor" />
            <x-metric-card icon="exclamation-circle" label="Totale mancante" value="€{{ number_format($totals['missing'], 2) }}" :color="$accentColor" />
            <x-metric-card icon="chart-pie" label="Completamento" value="{{ $totals['percent'] }}%" :color="$accentColor" />
        </div>

        <x-card>
            <x-section-header>Andamento incassi</x-section-header>
            @if (empty($monthlyCollected))
                <p class="text-sm text-gray-500 py-6 text-center">Nessun pagamento registrato per l'anno selezionato.</p>
            @else
                <div
                    x-data="{
                        async init() {
                            const ApexCharts = await window.loadApexCharts();
                            const isDark = document.documentElement.classList.contains('dark');
                            const chart = new ApexCharts(this.$refs.chart, {
                                chart: { type: 'area', height: 260, toolbar: { show: false }, fontFamily: 'inherit', foreColor: isDark ? '#9ca3af' : '#6b7280' },
                                series: [{ name: 'Incassato', data: {{ Illuminate\Support\Js::from(array_values($monthlyCollected)) }} }],
                                xaxis: { categories: {{ Illuminate\Support\Js::from(array_keys($monthlyCollected)) }}, axisBorder: { show: false }, axisTicks: { show: false } },
                                yaxis: { labels: { formatter: (v) => '€' + Math.round(v) } },
                                colors: ['{{ $accentHex }}'],
                                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0 } },
                                dataLabels: { enabled: false },
                                stroke: { curve: 'smooth', width: 2.5 },
                                grid: { borderColor: isDark ? 'rgba(255,255,255,0.08)' : 'rgba(148,163,184,0.15)', strokeDashArray: 3 },
                                tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: (v) => '€' + v.toFixed(2) } },
                            });
                            chart.render();
                        },
                    }"
                >
                    <div x-ref="chart"></div>
                </div>
            @endif
        </x-card>

        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <x-section-header>Per corso</x-section-header>
                <x-card class="p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    <th class="px-5 py-3 text-left">Corso</th>
                                    <th class="px-5 py-3 text-right">Atteso</th>
                                    <th class="px-5 py-3 text-right">Incassato</th>
                                    <th class="px-5 py-3 text-right">Mancante</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                @forelse ($byCourse as $row)
                                    <tr>
                                        <td class="px-5 py-3.5">
                                            <a href="{{ route('courses.show', $row['course']) }}" class="font-medium text-gray-900 dark:text-gray-100 hover:underline">
                                                {{ $row['course']->discipline->name }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3.5 text-right text-gray-600 dark:text-gray-300">€{{ number_format($row['expected'], 2) }}</td>
                                        <td class="px-5 py-3.5 text-right text-gray-600 dark:text-gray-300">€{{ number_format($row['collected'], 2) }}</td>
                                        <td class="px-5 py-3.5 text-right">
                                            @if ($row['missing'] > 0.005)
                                                <span class="font-semibold text-amber-600 dark:text-amber-400">€{{ number_format($row['missing'], 2) }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">Nessun corso per l'anno selezionato.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            <div>
                <x-section-header>Chi deve ancora pagare</x-section-header>
                <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0 max-h-96 overflow-y-auto">
                    @forelse ($whoOwes as $enrollment)
                        <a href="{{ route('members.show', $enrollment->user) }}" class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $enrollment->user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $enrollment->course->discipline->name }}</p>
                            </div>
                            <span class="text-sm font-semibold text-amber-600 dark:text-amber-400 shrink-0">€{{ number_format(abs($enrollment->balance()), 2) }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessuno risulta in debito.</p>
                    @endforelse
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
