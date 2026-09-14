@props(['href', 'icon', 'active' => false, 'color' => 'gray'])
@php $c = \App\Support\ModuleTheme::classes($color); @endphp

<a href="{{ $href }}"
   class="flex w-[4.5rem] shrink-0 flex-col items-center justify-center gap-1 py-2 text-xs font-medium
          {{ $active ? $c['text'] : 'text-gray-400 dark:text-gray-500' }}">
    <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" />
    <span>{{ $slot }}</span>
</a>
