{{-- Move up / down / remove for a row of resinaRows (expects `index` and `rows` in scope). --}}
<div class="flex shrink-0 items-center">
    <button type="button" @click="move(index, -1)" :disabled="index === 0" title="Sposta su"
            class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
        <x-heroicon-o-arrow-up class="h-4 w-4" />
    </button>
    <button type="button" @click="move(index, 1)" :disabled="index === rows.length - 1" title="Sposta giù"
            class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
        <x-heroicon-o-arrow-down class="h-4 w-4" />
    </button>
    <button type="button" @click="remove(index)" title="Togli"
            class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
        <x-heroicon-o-x-mark class="h-4 w-4" />
    </button>
</div>
