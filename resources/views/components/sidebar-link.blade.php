@props(['href', 'icon', 'active' => false, 'color' => 'gray', 'badge' => 0])
@php $c = \App\Support\ModuleTheme::classes($color); @endphp

<a href="{{ $href }}"
   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
          {{ $active ? $c['badge'].' text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' }}">
    <span class="relative shrink-0">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5" />
        @if ($badge > 0)
            <span class="absolute -top-1.5 -right-2 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold leading-none text-white ring-2 ring-white dark:ring-gray-950">{{ $badge > 9 ? '9+' : $badge }}</span>
        @endif
    </span>
    <span>{{ $slot }}</span>
</a>
