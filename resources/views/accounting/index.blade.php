@php
    $accentColor = $currentModule->color ?? 'gray';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
    $accent = \App\Support\ModuleTheme::classes($accentColor);
@endphp

<x-app-layout>
    <x-slot name="header">Contabilità</x-slot>

    <div class="space-y-6">
        <x-card>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <x-year-select :years="$years" :selected="$selectedYear" all-label="" />
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('accounting.payments') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                        <x-heroicon-o-clock class="h-4 w-4" /> Storico pagamenti
                    </a>
                    <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'register-payment')"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
                        <x-heroicon-o-banknotes class="h-4 w-4" /> Registra pagamento
                    </button>
                </div>
            </div>
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
            <div class="min-w-0">
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
                                                {{ $row['course']->displayName() }}
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

            <div class="min-w-0">
                <x-section-header>Chi deve ancora pagare</x-section-header>
                <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0 max-h-96 overflow-y-auto">
                    @forelse ($whoOwes as $row)
                        @php
                            $owesInitials = collect(explode(' ', $row['user']->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                        @endphp
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <a href="{{ route('members.show', $row['user']) }}" class="flex min-w-0 flex-1 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full {{ $accent['badge'] }} text-xs font-bold text-white">
                                    @if ($row['user']->avatarUrl())
                                        <img src="{{ $row['user']->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                                    @else
                                        {{ mb_strtoupper($owesInitials) }}
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $row['user']->name }}</p>
                                    <p class="text-xs text-gray-400">
                                        Versato €{{ number_format($row['paid'], 2) }}
                                        @if ($row['enrollments']->count() > 1)
                                            &middot; {{ $row['enrollments']->count() }} corsi
                                        @endif
                                    </p>
                                </span>
                            </a>
                            <span class="text-sm font-semibold text-amber-600 dark:text-amber-400 shrink-0">€{{ number_format($row['missing'], 2) }}</span>
                            <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'accounting-pay-{{ $row['user']->id }}')"
                                    class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                                <x-heroicon-o-banknotes class="h-4 w-4" />
                            </button>
                        </div>
                        <x-payment-modal :name="'accounting-pay-'.$row['user']->id" :enrollments="$row['enrollments']" />
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessuno risulta in debito.</p>
                    @endforelse
                </x-card>
            </div>
        </div>

        @if ($enrollments->isNotEmpty())
            <x-payment-modal name="register-payment" :enrollments="$enrollments" />
        @endif
    </div>
</x-app-layout>
