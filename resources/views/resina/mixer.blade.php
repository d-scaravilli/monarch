@php $accent = \App\Support\ModuleTheme::classes($currentModule?->color); @endphp

<x-app-layout>
    <x-slot name="header">Mixer</x-slot>

    <x-resina.payload id="resina-mixer" :data="$payload" />

    <div x-data="resinaMixer('resina-mixer')" class="space-y-4">
        <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Prova le tue combinazioni prima di sprecare colore: aggiungi i colori, cambia le gocce e guarda il risultato. Le
            ricette salvate restano nel tuo account.
        </p>
        <p x-show="error" x-cloak x-text="error" class="rounded-xl bg-red-50 px-4 py-2.5 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400"></p>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-card class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <select x-model="selected" aria-label="Colore da aggiungere"
                            class="min-w-0 flex-1 basis-48 rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <template x-for="group in paintGroups" :key="group.line">
                            <optgroup :label="group.line">
                                <template x-for="option in group.paints" :key="option.key">
                                    <option :value="option.key" x-text="option.label" :selected="option.key === selected"></option>
                                </template>
                            </optgroup>
                        </template>
                    </select>
                    <button type="button" @click="add()" class="rounded-xl px-4 py-2 text-sm font-semibold text-white {{ $accent['badge'] }}">Aggiungi</button>
                    <button type="button" @click="clear()" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">Svuota</button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-white/10">
                    <template x-for="row in rows" :key="row.key">
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="h-9 w-9 shrink-0 rounded-lg ring-1 ring-black/10 dark:ring-white/10" :style="row.style"></span>
                            <span class="min-w-0 flex-1 text-sm text-gray-800 dark:text-gray-100">
                                <span x-text="row.name"></span> <small class="whitespace-nowrap text-[11px] text-gray-400" x-text="row.code"></small>
                            </span>
                            <span class="inline-flex items-center rounded-xl ring-1 ring-gray-200 dark:ring-white/10">
                                <button type="button" @click="change(row.key, -1)" aria-label="Meno" class="flex h-9 w-9 items-center justify-center text-lg text-gray-600 dark:text-gray-300">−</button>
                                <output class="w-20 text-center text-sm tabular-nums text-gray-800 dark:text-gray-100" x-text="row.drops"></output>
                                <button type="button" @click="change(row.key, 1)" aria-label="Più" class="flex h-9 w-9 items-center justify-center text-lg text-gray-600 dark:text-gray-300">+</button>
                            </span>
                            <button type="button" @click="remove(row.key)" aria-label="Rimuovi" class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:text-red-600">
                                <x-heroicon-o-x-mark class="h-4 w-4" />
                            </button>
                        </div>
                    </template>
                </div>
                <p x-show="! rows.length" class="text-sm text-gray-500">
                    Il mixer è vuoto. Aggiungi un colore dal menu, oppure tocca un flacone nella <a href="{{ route('resina.home') }}" class="underline">Home</a>.
                </p>
            </x-card>

            <x-card class="space-y-3">
                <div class="flex h-40 items-end justify-end rounded-2xl p-3 ring-1 ring-black/10 dark:ring-white/10"
                     :style="result ? result.style : 'background: var(--resina-chip, #e5e7eb)'">
                    <code class="rounded-lg bg-black/40 px-2 py-1 text-sm text-white" x-text="result ? result.hex : '—'"></code>
                </div>

                <template x-if="result">
                    <div class="space-y-1.5 text-sm text-gray-700 dark:text-gray-300">
                        <div class="flex flex-wrap items-center gap-1.5"><b>Ricetta:</b> <x-resina.ingredients list="result.ingredients" /></div>
                        <template x-if="result.nearest">
                            <p>
                                <span x-show="result.nearest.same">È praticamente uguale a <b x-text="result.nearest.name"></b> <small class="text-[11px] text-gray-400" x-text="result.nearest.code"></small>: puoi usare direttamente quello.</span>
                                <span x-show="! result.nearest.same">Il colore singolo più vicino è <b x-text="result.nearest.name"></b> <small class="text-[11px] text-gray-400" x-text="result.nearest.code"></small>.</span>
                            </p>
                        </template>
                        <p x-show="result.wash" class="text-amber-700 dark:text-amber-300">Contiene uno Shade TMM: è un wash trasparente, il risultato reale sarà più velato.</p>
                        <p x-show="result.metal === 'result'" class="text-amber-700 dark:text-amber-300">Il risultato è metallico: mischialo su un piattino, non sulla tavolozza bagnata.</p>
                        <p x-show="result.metal === 'little'" class="text-amber-700 dark:text-amber-300">C'è poco metallico nel mix: brillerà poco.</p>
                    </div>
                </template>

                <div class="flex flex-wrap gap-2">
                    <input type="text" x-model="name" placeholder="Nome della ricetta" @keydown.enter.prevent="saveMix()"
                           class="min-w-0 flex-1 basis-40 rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <button type="button" @click="saveMix()" :disabled="! rows.length"
                            class="rounded-xl px-4 py-2 text-sm font-semibold text-white disabled:opacity-40 {{ $accent['badge'] }}">Salva ricetta</button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-white/10">
                    <template x-for="entry in saved" :key="entry.id">
                        <div class="flex items-center gap-3 py-2.5">
                            <span class="h-7 w-7 shrink-0 rounded-lg ring-1 ring-black/10" :style="savedStyle(entry)"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium text-gray-900 dark:text-gray-100" x-text="entry.name"></span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400" x-text="savedText(entry)"></span>
                            </span>
                            <button type="button" @click="openSaved(entry)" class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5">Apri</button>
                            <button type="button" @click="deleteSaved(entry)" aria-label="Elimina" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:text-red-600">
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>
                        </div>
                    </template>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
