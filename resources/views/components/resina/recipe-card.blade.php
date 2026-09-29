{{-- A recipe card, rendered by Alpine from decorateRecipe() (resources/js/resina/view.js). Expects `r` in scope. --}}
<article {{ $attributes->class(['scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10']) }} :id="'r-' + r.slug">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100" x-text="r.title"></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400" x-show="r.who" x-text="r.who"></p>
        </div>
        <div class="flex items-center gap-3">
            <div>
                <p class="mb-1 text-[11px] text-gray-400">Toni, dal più scuro al più chiaro</p>
                <div class="inline-flex overflow-hidden rounded-lg ring-1 ring-black/10 dark:ring-white/10">
                    <template x-for="(tone, t) in r.scale" :key="t">
                        <span class="h-6 w-6" :style="tone.style" :title="tone.title"></span>
                    </template>
                </div>
            </div>
            <a x-show="r.editHref" :href="r.editHref" title="Modifica ricetta"
               class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5">
                <x-heroicon-o-pencil-square class="h-5 w-5" />
            </a>
        </div>
    </header>

    <p x-show="r.tip" x-text="r.tip" class="mt-3 rounded-xl bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"></p>
    <p class="mt-3 text-xs text-gray-400">I passaggi sono nell'ordine in cui si dipingono.</p>

    <div class="divide-y divide-gray-100 dark:divide-white/10">
        <template x-for="(s, i) in r.view" :key="i">
            <x-resina.step-row />
        </template>
    </div>
</article>
