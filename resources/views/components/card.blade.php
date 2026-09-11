@props(['class' => ''])

<div {{ $attributes->class(['rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100', $class]) }}>
    {{ $slot }}
</div>
