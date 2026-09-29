@php $accent = \App\Support\ModuleTheme::classes($currentModule?->color); @endphp

<x-app-layout>
    <x-slot name="header">Trova un colore</x-slot>

    <x-resina.payload id="resina-finder" :data="$payload" />

    <div x-data="resinaFinder('resina-finder')" class="max-w-4xl space-y-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Scegli il colore che vuoi ottenere, anche copiandolo da un'immagine di riferimento. La pagina prova migliaia di
            combinazioni dei tuoi colori e ti mostra le ricette più vicine, in gocce. Se il colore è impossibile da ottenere bene,
            ti dice anche quale Vallejo comprare.
        </p>

        <x-card class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <input type="color" :value="hex.toLowerCase()" @input="picked($event.target.value)" aria-label="Colore da ottenere"
                       class="h-11 w-16 cursor-pointer rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-white/10 dark:bg-white/5">
                <input type="text" x-model="hexInput" @change="hexTyped()" @keydown.enter.prevent="hexTyped()" aria-label="Codice esadecimale"
                       class="w-28 rounded-xl border-gray-200 bg-gray-50 font-mono text-sm uppercase dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                <div class="inline-flex rounded-xl bg-gray-100 p-1 text-sm font-medium dark:bg-white/5">
                    <button type="button" @click="three = false; run()" class="rounded-lg px-3 py-1.5" :class="! three ? 'bg-white shadow-sm dark:bg-gray-900' : 'text-gray-500'">Fino a 2 colori</button>
                    <button type="button" @click="three = true; run()" class="rounded-lg px-3 py-1.5" :class="three ? 'bg-white shadow-sm dark:bg-gray-900' : 'text-gray-500'">Fino a 3 colori</button>
                </div>
                <button type="button" @click="run()" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white {{ $accent['badge'] }}">Calcola la ricetta</button>
            </div>

            <div>
                <p class="mb-2 text-xs font-medium text-gray-400">Colori rapidi</p>
                <div class="flex max-h-40 flex-wrap gap-1.5 overflow-y-auto">
                    <template x-for="(preset, n) in presets" :key="n">
                        <button type="button" @click="choose(preset.hex)"
                                class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 py-1 pl-1 pr-2.5 text-xs text-gray-700 ring-1 ring-gray-100 hover:bg-gray-100 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10"
                                :class="preset.hex === hex && 'ring-2 ring-gray-900 dark:ring-white'">
                            <span class="h-4 w-4 rounded-full ring-1 ring-black/10" :style="'background:' + preset.hex"></span>
                            <span x-text="preset.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        </x-card>

        <template x-if="buy">
            <x-resina.buy-card suggestion="buy" />
        </template>

        <div class="space-y-3" :class="busy && 'opacity-60'">
            <p x-show="busy && ! results.length" class="flex items-center gap-2 text-sm text-gray-400">
                <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" /> Calcolo…
            </p>
            <template x-for="(r, n) in results" :key="n + r.hex">
                <x-card class="flex flex-wrap items-center gap-4">
                    <div class="flex overflow-hidden rounded-xl ring-1 ring-black/10 dark:ring-white/10">
                        <span class="h-12 w-12" :style="'background:' + hex" title="Obiettivo"></span>
                        <span class="h-12 w-12" :style="'background:' + r.hex" title="Risultato"></span>
                    </div>
                    <div class="min-w-0 flex-1 basis-56">
                        <x-resina.ingredients list="r.ingredients" />
                        <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            Risultato <code x-text="r.hex"></code> · obiettivo <code x-text="hex"></code>
                            <x-resina.closeness value="r.closeness" />
                        </p>
                    </div>
                    <a :href="r.mixerHref" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">Apri nel mixer</a>
                </x-card>
            </template>
        </div>

        <p class="flex flex-wrap items-center gap-1.5 text-xs text-gray-400">
            Vicinanza:
            <x-resina.closeness value="['ok', 'quasi identico']" /> non vedrai differenza ·
            <x-resina.closeness value="['mid', 'vicino']" /> differenza leggera ·
            <x-resina.closeness value="['far', 'approssimativo']" /> è il meglio possibile con i tuoi colori.
        </p>
    </div>
</x-app-layout>
