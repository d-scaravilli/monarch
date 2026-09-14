@php $accentColor = $currentModule->color ?? 'blue'; @endphp

<x-app-layout>
    <x-slot name="header">Dashboard XX</x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            <x-metric-card icon="users" label="Utenti totali" :value="$totalUsers" :color="$accentColor" />
            <x-metric-card icon="shield-check" label="Admin" :value="$roleDistribution['admin']" :color="$accentColor" />
            <x-metric-card icon="academic-cap" label="Instructor" :value="$roleDistribution['instructor']" :color="$accentColor" />
            <x-metric-card icon="user-group" label="Member" :value="$roleDistribution['member']" :color="$accentColor" />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <x-card>
                <x-section-header>Attivi vs disabilitati</x-section-header>
                @php $activePct = $totalUsers > 0 ? round($activeUsers / $totalUsers * 100) : 0; @endphp
                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Attivi</span>
                            <span class="text-gray-400">{{ $activeUsers }} ({{ $activePct }}%)</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full bg-green-500" style="width: {{ $activePct }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Disabilitati</span>
                            <span class="text-gray-400">{{ $disabledUsers }} ({{ 100 - $activePct }}%)</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full bg-red-400" style="width: {{ 100 - $activePct }}%"></div>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-section-header>Distribuzione ruoli</x-section-header>
                <div class="space-y-3">
                    @foreach ($roleDistribution as $role => $count)
                        @php $pct = $totalUsers > 0 ? round($count / $totalUsers * 100) : 0; @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $role }}</span>
                                <span class="text-gray-400">{{ $count }} ({{ $pct }}%)</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full {{ \App\Support\ModuleTheme::classes($accentColor)['badge'] }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <x-section-header>Moduli attivi</x-section-header>
                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $activeModules }}</p>
                <p class="text-xs text-gray-400">moduli visibili nel launcher</p>
            </x-card>
        </div>
    </div>
</x-app-layout>
