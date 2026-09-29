{{-- A character tile of a project page, rendered by Alpine. Expects `c` in scope. --}}
<a :href="c.href" class="block overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:ring-gray-300 dark:bg-gray-900 dark:ring-white/10 dark:hover:ring-white/20">
    <div class="flex h-14">
        <template x-for="z in c.palette" :key="z.key">
            <span class="flex-1" :style="z.style" :title="z.name"></span>
        </template>
    </div>
    <div class="p-3">
        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="c.name"></p>
        <p class="truncate text-xs text-gray-500 dark:text-gray-400" x-text="[c.subtitle, c.alias].filter(Boolean).join(' · ')"></p>
    </div>
</a>
