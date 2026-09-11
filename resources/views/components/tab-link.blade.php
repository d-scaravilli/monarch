@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}"
   class="flex flex-1 flex-col items-center justify-center gap-1 py-2 text-xs font-medium
          {{ $active ? 'text-gray-900' : 'text-gray-400' }}">
    <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" />
    <span>{{ $slot }}</span>
</a>
