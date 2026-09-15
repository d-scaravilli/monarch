@props(['icon', 'class' => 'h-6 w-6'])

{{-- Heroicons has no sword or dumbbell icon, so these two are hand-drawn
     to match the outline style (stroke-width 1.5, round caps/joins); every
     other choice is a plain heroicon via x-dynamic-component. --}}
@switch($icon)
    @case('sword')
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 4 4 20" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 11.5 12.5 16.5" />
            <circle cx="4" cy="20" r="1.2" fill="currentColor" stroke="none" />
        </svg>
        @break

    @case('dumbbell')
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8M6 7v10M18 7v10M3.5 9.5v5M20.5 9.5v5" />
        </svg>
        @break

    @default
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="{{ $class }}" />
@endswitch
