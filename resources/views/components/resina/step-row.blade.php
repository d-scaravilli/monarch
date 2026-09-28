@php $accent = \App\Support\ModuleTheme::classes($currentModule?->color); @endphp

{{--
    One recipe step, rendered by Alpine from decorateStep() in
    resources/js/resina/index.js. Expects `s` (the decorated step) and
    `i` (its index) in scope; the slot sits under the hex and Mixer
    button (the "fatto" checkbox on the character sheet).
--}}
<div class="flex flex-wrap gap-x-3 gap-y-2 py-3" :class="s.optional && 'opacity-80'">
    <div class="flex shrink-0 flex-col items-center gap-2">
        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="i + 1"></span>
        <span class="h-10 w-10 rounded-xl ring-1 ring-black/10 dark:ring-white/10" :style="s.swatch"></span>
    </div>

    <div class="min-w-0 flex-1 basis-48">
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="s.role"></span>
            <template x-if="s.closeness">
                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                      :class="{ ok: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400', mid: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400', far: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' }[s.closeness[0]]"
                      x-text="s.closeness[1]"></span>
            </template>
            <template x-if="s.optional">
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 dark:bg-white/10 dark:text-gray-400">facoltativo</span>
            </template>
            <template x-if="s.technique">
                <span>
                    <a x-show="s.technique.href" :href="s.technique.href" title="Come si fa"
                       class="rounded-full px-2 py-0.5 text-[11px] font-medium underline-offset-2 hover:underline {{ $accent['soft'] }} {{ $accent['text'] }} dark:bg-white/10"
                       x-text="s.technique.label"></a>
                    <span x-show="! s.technique.href"
                          class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $accent['soft'] }} {{ $accent['text'] }} dark:bg-white/10"
                          x-text="s.technique.label"></span>
                </span>
            </template>
            <a :href="s.brush.href" :title="s.brush.title"
               class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300">
                🖌 <span x-text="s.brush.label"></span>
            </a>
        </div>

        <div class="mt-1.5 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm">
            <template x-for="(ingredient, n) in s.ingredients" :key="ingredient.key">
                <span class="inline-flex items-center gap-1.5">
                    <span x-show="n > 0" class="text-gray-300 dark:text-gray-600">+</span>
                    <span class="h-3.5 w-3.5 shrink-0 rounded-full ring-1 ring-black/10 dark:ring-white/10" :style="ingredient.style"></span>
                    <span class="text-gray-700 dark:text-gray-200">
                        <span x-text="ingredient.drops"></span>
                        <span x-text="ingredient.name"></span>
                        <small class="text-[11px] text-gray-400 dark:text-gray-500 whitespace-nowrap" x-text="ingredient.code"></small>
                    </span>
                </span>
            </template>
            <span x-show="! s.ingredients.length" class="text-xs text-gray-400">colore non disponibile</span>
        </div>

        <p x-show="s.usage" x-text="s.usage" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>

        <template x-if="s.coverage">
            <div class="mt-1.5 flex items-center gap-2">
                <div class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10" aria-hidden="true">
                    <div class="h-full rounded-full {{ $accent['badge'] }}" :style="'width:' + s.coverage.pct + '%'"></div>
                </div>
                <span class="text-xs text-gray-400" x-text="s.coverage.label"></span>
            </div>
        </template>
    </div>

    <div class="flex w-full shrink-0 items-center justify-end gap-2 sm:w-auto sm:flex-col sm:items-end">
        <code class="text-xs text-gray-500 dark:text-gray-400" x-text="s.hex"></code>
        <a x-show="s.mixerHref" :href="s.mixerHref"
           class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5">Mixer</a>
        {{ $slot }}
    </div>
</div>
