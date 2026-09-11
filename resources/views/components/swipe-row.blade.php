@props(['action', 'method' => 'DELETE', 'deleteLabel' => 'Elimina'])

<div x-data="{ dx: 0, dragging: false, startX: 0, open: false }" class="relative overflow-hidden">
    <div class="absolute inset-y-0 right-0 flex">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if (strtoupper($method) !== 'POST')
                @method($method)
            @endif
            <button type="submit" class="flex h-full w-20 items-center justify-center bg-red-600 text-sm font-semibold text-white">
                {{ $deleteLabel }}
            </button>
        </form>
    </div>
    <div
        class="relative bg-white dark:bg-gray-900"
        :class="dragging ? '' : 'transition-transform duration-200'"
        :style="`transform: translateX(${dx}px)`"
        @touchstart="dragging = true; startX = $event.touches[0].clientX"
        @touchmove="if (dragging) { dx = Math.min(0, Math.max(-80, $event.touches[0].clientX - startX)) }"
        @touchend="dragging = false; open = dx < -40; dx = open ? -80 : 0"
        @click="if (open) { dx = 0; open = false }"
    >
        {{ $slot }}
    </div>
</div>
