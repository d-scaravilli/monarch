@props(['icon', 'label', 'value', 'color' => 'gray', 'trend' => null])
@php $c = \App\Support\ModuleTheme::classes($color); @endphp

<div {{ $attributes->class(['rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-100 dark:ring-white/10']) }}>
    <div class="flex items-start justify-between">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $c['soft'] }} dark:bg-white/5">
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5 {{ $c['text'] }}" />
        </span>

        @if ($trend)
            @php
                $up = $trend['direction'] === 'up';
                $trendColor = $up ? 'text-green-600 bg-green-50 dark:bg-green-500/10' : 'text-red-500 bg-red-50 dark:bg-red-500/10';
            @endphp
            <span class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-xs font-medium {{ $trendColor }}">
                <x-dynamic-component :component="'heroicon-o-arrow-trending-'.($up ? 'up' : 'down')" class="h-3 w-3" />
                {{ $trend['label'] }}
            </span>
        @endif
    </div>

    <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">{{ $value }}</p>
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $label }}</p>
</div>
