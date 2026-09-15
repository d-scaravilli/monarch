@props(['href', 'icon', 'active' => false, 'color' => 'gray', 'badge' => 0])
@php $c = \App\Support\ModuleTheme::classes($color); @endphp

<a href="{{ $href }}"
   class="flex w-[4.5rem] shrink-0 flex-col items-center justify-center gap-1 py-2 text-xs font-medium
          {{ $active ? $c['text'] : 'text-gray-400 dark:text-gray-500' }}">
    <span class="relative">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" />
        @if ($badge > 0)
            <span class="absolute -top-1 -right-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold leading-none text-white ring-2 ring-white dark:ring-gray-900">{{ $badge > 9 ? '9+' : $badge }}</span>
        @endif
    </span>
    <span>{{ $slot }}</span>
</a>
