@php $accent = \App\Support\ModuleTheme::classes($currentModule?->color); @endphp

<x-app-layout>
    <x-slot name="header">Analizza una foto</x-slot>

    <x-resina.payload id="resina-photo" :data="$payload" />

    <div x-data="resinaPhotoAnalysis('resina-photo')" class="space-y-4">
        <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400">
            Carica una foto della figura, di una statua già dipinta o un'immagine dell'anime. Scegli il personaggio (o descrivilo
            se non è in elenco) e ottieni una guida completa: zone, ricette in gocce con i tuoi colori, pennelli e ordine di lavoro.
            La guida viene salvata in «Le mie figure». La foto resta privata: la vedi solo tu.
        </p>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-card class="space-y-3">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">1. Carica l'immagine</h3>
                <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-4 py-8 text-center text-sm transition"
                       :class="dragging ? 'border-gray-900 bg-gray-50 dark:border-white dark:bg-white/5' : 'border-gray-200 text-gray-500 dark:border-white/15 dark:text-gray-400'"
                       @dragover.prevent="dragging = true" @dragenter.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dropped($event)">
                    <input type="file" accept="image/*" class="hidden" @change="pick($event.target.files[0]); $event.target.value = ''">
                    <x-heroicon-o-photo class="h-8 w-8 text-gray-400" />
                    <span x-text="loading ? 'Preparo la foto…' : (loaded ? 'Cambia immagine' : 'Tocca per scegliere una foto, oppure trascinala qui')"></span>
                </label>
                <canvas x-ref="canvas" x-show="loaded" x-cloak @click="sample($event)" class="w-full cursor-crosshair rounded-xl"></canvas>
                <p x-show="message && ! loaded" x-cloak x-text="message" class="rounded-xl bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400"></p>
                <p x-show="loaded" x-cloak class="text-xs text-gray-400">Tocca un punto dell'immagine per aggiungere quel colore all'elenco.</p>
            </x-card>

            <x-card class="space-y-3">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">2. Di chi si tratta?</h3>
                <select x-model="characterId" @change="characterChanged()" aria-label="Personaggio"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">— personaggio nuovo o non in elenco —</option>
                    <template x-for="project in projects" :key="project.name">
                        <optgroup :label="project.name">
                            <template x-for="c in project.characters" :key="c.id">
                                <option :value="String(c.id)" x-text="c.name + (c.subtitle ? ' · ' + c.subtitle : '')"></option>
                            </template>
                        </optgroup>
                    </template>
                </select>

                <template x-if="character">
                    <div class="space-y-2">
                        <template x-if="character.versions.length">
                            <div class="space-y-1">
                                <label class="text-xs text-gray-500">Versione</label>
                                <select x-model="versionId" aria-label="Versione"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                    <template x-for="v in character.versions" :key="v.id">
                                        <option :value="String(v.id)" x-text="v.label"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Scheda già pronta: <a :href="character.href" class="font-medium underline" x-text="'apri ' + character.name"></a>.
                            La foto serve a personalizzarla con i colori della tua reference.
                        </p>
                    </div>
                </template>

                <div x-show="! character" class="grid gap-2">
                    <x-text-input x-model="name" placeholder="Nome del personaggio (es. Goku, Batman)" class="w-full" />
                    <x-text-input x-model="series" placeholder="Serie o progetto (es. Dragon Ball)" class="w-full" />
                    <textarea x-model="notes" rows="3" placeholder="Note: versione, colori particolari, cosa vuoi ottenere…"
                              class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100"></textarea>
                </div>
            </x-card>
        </div>

        <x-card class="space-y-3">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">3. I colori della foto</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Calcolati sul tuo dispositivo. Per ogni colore scegli a quale zona appartiene e, se vuoi, dagli un nome (es.
                «Mantello»). Ignora lo sfondo.
            </p>
            <p x-show="! colors.length" class="text-sm text-gray-400" x-text="loaded ? 'Nessun colore. Tocca l\'immagine per aggiungerne.' : 'Carica prima un\'immagine.'"></p>

            <div class="divide-y divide-gray-100 dark:divide-white/10">
                <template x-for="(c, index) in colors" :key="c.id">
                    <div class="flex flex-wrap items-center gap-3 py-3">
                        <span class="h-10 w-10 shrink-0 rounded-xl ring-1 ring-black/10 dark:ring-white/10" :style="'background:' + c.hex"></span>
                        <div class="min-w-0 flex-1 basis-52">
                            <p class="flex flex-wrap items-center gap-2 text-sm">
                                <code class="text-gray-700 dark:text-gray-200" x-text="c.hex"></code>
                                <span x-show="c.picked" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600 dark:bg-white/10 dark:text-gray-300">campionato</span>
                                <span x-show="! c.picked && c.pct" class="text-xs text-gray-400" x-text="Math.round(c.pct * 100) + '% della foto'"></span>
                            </p>
                            <p x-show="! c.best" class="mt-1 flex items-center gap-1.5 text-xs text-gray-400"><x-heroicon-o-arrow-path class="h-3.5 w-3.5 animate-spin" /> Calcolo…</p>
                            <template x-if="c.best">
                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                                    <x-resina.ingredients list="c.best.ingredients" class="text-xs" />
                                    <x-resina.closeness value="c.best.closeness" />
                                </div>
                            </template>
                        </div>
                        <div class="flex w-full items-center gap-2 sm:w-auto">
                            <select x-model="c.tab" aria-label="Zona"
                                    class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm sm:w-40 sm:flex-none dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                <template x-for="t in zoneTabs" :key="t[0]">
                                    <option :value="t[0]" x-text="t[1]" :selected="t[0] === c.tab"></option>
                                </template>
                            </select>
                            <input type="text" x-model="c.name" placeholder="Nome zona" aria-label="Nome zona (facoltativo)"
                                   class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm sm:w-36 sm:flex-none dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <button type="button" @click="removeColor(index)" aria-label="Rimuovi" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-400 hover:text-red-600">
                                <x-heroicon-o-x-mark class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </x-card>

        <div class="flex flex-wrap items-center gap-3">
            <button type="button" @click="createGuide()" :disabled="saving"
                    class="rounded-xl px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50 {{ $accent['badge'] }}"
                    x-text="saving ? 'Creo la guida…' : 'Crea la guida'"></button>
            <p x-show="message" x-cloak x-text="message" class="text-sm text-red-600 dark:text-red-400"></p>
        </div>
    </div>
</x-app-layout>
