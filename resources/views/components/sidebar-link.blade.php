@props(['href', 'icon', 'active' => false, 'color' => 'gray'])
@php $c = \App\Support\ModuleTheme::classes($color); @endphp

<a href="{{ $href }}"
   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
          {{ $active ? $c['badge'].' text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' }}">
    <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5 shrink-0" />
    <span>{{ $slot }}</span>
</a>
