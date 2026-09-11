@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}"
   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
          {{ $active ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
    <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5 shrink-0" />
    <span>{{ $slot }}</span>
</a>
