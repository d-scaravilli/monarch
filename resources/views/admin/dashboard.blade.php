@php $accentColor = $currentModule->color ?? 'blue'; @endphp

<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            <x-metric-card icon="users" label="Utenti totali" :value="$totalUsers" :color="$accentColor" />
            <x-metric-card icon="shield-check" label="Admin" :value="$roleDistribution['admin']" :color="$accentColor" />
            <x-metric-card icon="academic-cap" label="Instructor" :value="$roleDistribution['instructor']" :color="$accentColor" />
            <x-metric-card icon="user-group" label="Member" :value="$roleDistribution['member']" :color="$accentColor" />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <x-card class="lg:col-span-2">
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

        <div>
            <x-section-header>Utenti recenti</x-section-header>
            <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                @foreach ($recentUsers as $recentUser)
                    <a href="{{ route('admin.users.edit', $recentUser) }}" class="flex items-center justify-between px-5 py-3.5">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $recentUser->name }}</p>
                            <p class="text-xs text-gray-400">{{ $recentUser->email }}</p>
                        </div>
                        <span class="text-sm text-gray-400">{{ $recentUser->created_at->translatedFormat('d M Y') }}</span>
                    </a>
                @endforeach
            </x-card>
        </div>
    </div>
</x-app-layout>
