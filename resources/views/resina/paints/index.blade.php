<x-app-layout>
    <x-slot name="header">I miei colori</x-slot>

    <x-resina.payload id="resina-shop" :data="$payload" />

    <div class="space-y-6">
        <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            I flaconi che possiedi, con il codice Vallejo e a cosa servono. I valori esadecimali sono indicativi. Quando compri
            un colore nuovo, aggiungilo qui: entra subito nel mixer e nella ricerca delle ricette.
        </p>

        <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
            Il set Tanned Skin 72.380 contiene una base, un'ombra e due luci. Qui sono usati così: <b>ombra</b> Pelle Barbaro
            72.071, <b>base</b> Pelle Elfi 72.004, <b>luci</b> Pelle 72.099 e Carne Elfica 72.098. Se il foglietto della
            confezione indica altri ruoli, segui il foglietto.
        </div>

        <livewire:resina-paints-table />

        <div>
            <x-section-header>Aggiungi un colore</x-section-header>
            <x-card>
                <form method="POST" action="{{ route('resina.paints.store') }}" class="grid gap-3 sm:grid-cols-[1fr_9rem_auto_9rem_auto] sm:items-end">
                    @csrf
                    <div class="space-y-1">
                        <x-input-label for="paint-name" value="Nome" />
                        <x-text-input id="paint-name" name="name" value="{{ old('name') }}" placeholder="Es. Royal Purple" class="w-full" required />
                    </div>
                    <div class="space-y-1">
                        <x-input-label for="paint-code" value="Codice" />
                        <x-text-input id="paint-code" name="code" value="{{ old('code') }}" placeholder="Es. 72.016" class="w-full" />
                    </div>
                    <div class="space-y-1">
                        <x-input-label for="paint-hex" value="Colore" />
                        <input id="paint-hex" type="color" name="hex" value="{{ old('hex', '#5b3a86') }}"
                               class="h-[42px] w-full cursor-pointer rounded-xl border border-gray-200 bg-gray-50 p-1 sm:w-14 dark:border-white/10 dark:bg-white/5">
                    </div>
                    <div class="space-y-1">
                        <x-input-label for="paint-type" value="Tipo" />
                        <select id="paint-type" name="type" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="normal" @selected(old('type') === 'normal')>Normale</option>
                            <option value="metallic" @selected(old('type') === 'metallic')>Metallico</option>
                            <option value="wash" @selected(old('type') === 'wash')>Wash</option>
                            <option value="airbrush" @selected(old('type') === 'airbrush')>Aerografo</option>
                        </select>
                    </div>
                    <x-primary-button class="justify-center">Aggiungi</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                <x-input-error :messages="$errors->get('hex')" class="mt-2" />
                <p class="mt-3 text-xs text-gray-400">
                    Per l'hex, scegli il colore più simile al flacone o copia quello della scheda del produttore. Oppure usa
                    «L'ho comprato» qui sotto.
                </p>
            </x-card>
        </div>

        <div x-data="resinaShopSuggestions('resina-shop')">
            <x-section-header>Colori consigliati da comprare</x-section-header>
            <p class="mb-3 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Alcuni colori con i tuoi flaconi vengono spenti. Questi Vallejo Game Color li risolvono (hex indicativo). Quando
                ne compri uno, premi «L'ho comprato» e lo trovi subito nel mixer e nelle ricerche.
            </p>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="s in suggestions" :key="s.code">
                    <x-card class="flex flex-col gap-3">
                        <div class="flex items-start gap-3">
                            <span class="h-11 w-11 shrink-0 rounded-xl ring-1 ring-black/10 dark:ring-white/10" :style="'background:' + s.hex"></span>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    <span x-text="s.name"></span>
                                    <small class="text-[11px] font-normal text-gray-400" x-text="s.code"></small>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="s.why"></p>
                            </div>
                        </div>

                        <div x-show="! s.owned" class="text-sm">
                            <p class="flex items-center gap-2 text-xs text-gray-400" x-show="! s.best"><x-heroicon-o-arrow-path class="h-3.5 w-3.5 animate-spin" /> Calcolo…</p>
                            <template x-if="s.best">
                                <div>
                                    <p class="mb-1 flex items-center gap-2 text-xs text-gray-500">
                                        Il più vicino con i tuoi colori
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                              :class="{ ok: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400', mid: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400', far: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' }[s.best.closeness[0]]"
                                              x-text="s.best.closeness[1]"></span>
                                    </p>
                                    <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                                        <template x-for="(ingredient, n) in s.best.ingredients" :key="ingredient.key">
                                            <span class="inline-flex items-center gap-1.5">
                                                <span x-show="n > 0" class="text-gray-300">+</span>
                                                <span class="h-3.5 w-3.5 rounded-full ring-1 ring-black/10" :style="ingredient.style"></span>
                                                <span class="text-gray-700 dark:text-gray-200">
                                                    <span x-text="ingredient.drops"></span> <span x-text="ingredient.name"></span>
                                                    <small class="text-[11px] text-gray-400" x-text="ingredient.code"></small>
                                                </span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-auto">
                            <template x-if="s.owned">
                                <x-badge color="green">Ce l'hai</x-badge>
                            </template>
                            <template x-if="! s.owned">
                                <form method="POST" :action="s.buyUrl">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                                        L'ho comprato
                                    </button>
                                </form>
                            </template>
                        </div>
                    </x-card>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
