@props(['href', 'icon', 'title'])

<a href="{{ $href }}">
    <x-card class="flex h-full items-start gap-4 transition hover:ring-gray-300 dark:hover:ring-white/20">
        <x-module-badge :icon="$icon" :color="$currentModule?->color" size="h-10 w-10" />
        <div>
            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $slot }}</p>
        </div>
    </x-card>
</a>
