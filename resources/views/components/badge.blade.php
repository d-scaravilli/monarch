@props(['color' => 'gray'])

@php
$colors = [
    'gray' => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
    'green' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    'red' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', $colors[$color] ?? $colors['gray']]) }}>
    {{ $slot }}
</span>
