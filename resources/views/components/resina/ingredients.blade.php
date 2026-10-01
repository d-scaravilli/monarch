@props(['list'])

{{-- A mix in drops, rendered by Alpine from the ingredient list named in `list`: dot, drops, name and code. --}}
<div {{ $attributes->class(['flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm']) }}>
    <template x-for="(ingredient, n) in {{ $list }}" :key="ingredient.key">
        <span class="inline-flex items-center gap-1.5">
            <span x-show="n > 0" class="text-gray-300 dark:text-gray-600">+</span>
            <span class="h-3.5 w-3.5 shrink-0 rounded-full ring-1 ring-black/10 dark:ring-white/10" :style="ingredient.style"></span>
            <span class="text-gray-700 dark:text-gray-200">
                <span x-text="ingredient.drops"></span>
                <span x-text="ingredient.name"></span>
                <small class="whitespace-nowrap text-[11px] text-gray-400 dark:text-gray-500" x-text="ingredient.code"></small>
            </span>
        </span>
    </template>
</div>
