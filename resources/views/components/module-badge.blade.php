@props(['icon', 'color' => 'gray', 'size' => 'h-10 w-10', 'image' => null])
@php $c = \App\Support\ModuleTheme::classes($color); @endphp

@if ($image)
    <span {{ $attributes->class(['flex items-center justify-center rounded-2xl overflow-hidden shrink-0', $size]) }}>
        <img src="{{ $image }}" alt="" class="h-full w-full object-cover">
    </span>
@else
    <span {{ $attributes->class(['flex items-center justify-center rounded-2xl text-white shrink-0', $c['badge'], $size]) }}>
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-1/2 w-1/2" />
    </span>
@endif
