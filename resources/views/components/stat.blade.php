@props(['value', 'label', 'color' => 'gray'])
@php $c = \App\Support\ModuleTheme::classes($color); @endphp

<div {{ $attributes->class(['rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10']) }}>
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $label }}</p>
    <p class="mt-1 text-3xl font-bold {{ $c['text'] }}">{{ $value }}</p>
</div>
