@props(['icon', 'color' => 'gray', 'size' => 'h-10 w-10'])
@php $c = \App\Support\ModuleTheme::classes($color); @endphp

<span {{ $attributes->class(['flex items-center justify-center rounded-2xl text-white shrink-0', $c['badge'], $size]) }}>
    <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-1/2 w-1/2" />
</span>
