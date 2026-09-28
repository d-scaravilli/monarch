<x-app-layout>
    <x-slot name="header">I miei pennelli</x-slot>

    <x-resina.payload id="resina-brushes" :data="$payload" />

    <div x-data="resinaBrushKit('resina-brushes')" class="max-w-3xl space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Ogni passaggio delle ricette e delle schede ti dice quale pennello usare, scelto tra quelli di questo elenco. Il kit
            Nicpro che hai contiene 12 pennelli da dettaglio e 4 drybrush di forme e misure diverse.
        </p>

        <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
            <b>Kit Nicpro da 16 pennelli.</b> Piatti 2 e 0, angolato 1, tondi 2, 1, 0, 3/0, 5/0 e 10/0, liner 3/0 e 5/0, Spot
            Detail 20/0, drybrush 9, 7, 5 e 3. Il numero è stampato sul manico. Se compri altri pennelli, aggiungili qui: le
            indicazioni in tutto il modulo si aggiornano da sole.
        </div>

        <x-card class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-gray-500 dark:text-gray-400"><span x-text="brushes.length"></span> pennelli</p>
                <span x-show="busy" x-cloak class="text-xs text-gray-400">Salvo…</span>
            </div>
            <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600"></p>

            <div class="divide-y divide-gray-100 dark:divide-white/10">
                <template x-for="brush in brushes" :key="brush.id">
                    <div class="flex flex-wrap items-center gap-2 py-2.5">
                        <select :value="brush.type" @change="save(brush, 'type', $event.target.value)" aria-label="Tipo"
                                class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm sm:flex-none sm:w-40 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <template x-for="t in types" :key="t.code">
                                <option :value="t.code" x-text="t.label" :selected="t.code === brush.type"></option>
                            </template>
                        </select>
                        <input type="text" :value="brush.size" @change="save(brush, 'size', $event.target.value)" maxlength="10"
                               aria-label="Misura" placeholder="es. 3/0, 1, M"
                               class="w-24 rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <label class="flex items-center gap-2 px-1 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" :checked="brush.metallic_only" @change="save(brush, 'metallic_only', $event.target.checked)"
                                   class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                            solo metallici
                        </label>
                        <button type="button" @click="remove(brush)" title="Rimuovi"
                                class="ml-auto flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                            <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                    </div>
                </template>
                <p x-show="! brushes.length" x-cloak class="py-4 text-sm text-gray-500">Nessun pennello: aggiungine uno o ripristina il kit.</p>
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="button" @click="add()" :disabled="busy"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                    <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi pennello
                </button>
                <button type="button" @click="reset()" :disabled="busy"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                    <x-heroicon-o-arrow-path class="h-4 w-4" /> Ripristina il kit Nicpro
                </button>
            </div>

            <p class="text-xs text-gray-400">
                Misure dei tondi: più è alto il numero prima di «/0», più il pennello è fine (10/0 è finissimo, 3/0 = 000, 2/0 = 00).
                Dopo lo 0 si sale: 1, 2, 4 sono sempre più grandi. I drybrush vanno dal 3 (piccolo) al 9 (grande). Un solo pennello
                alla volta può essere «solo metallici».
            </p>
        </x-card>

        <div>
            <x-section-header>A cosa serve ognuno</x-section-header>
            <x-card class="overflow-hidden p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:border-white/10">
                            <th class="px-4 py-3 text-left">Funzione</th>
                            <th class="px-4 py-3 text-left">Il tuo pennello</th>
                            <th class="hidden px-4 py-3 text-left sm:table-cell">Quando lo usi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        <template x-for="slot in slots" :key="slot.key">
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <span class="font-medium text-gray-900 dark:text-gray-100" x-text="slot.name"></span>
                                    <span class="mt-0.5 block text-xs text-gray-500 sm:hidden" x-text="slot.description"></span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span x-show="slot.brush" x-text="slot.brush" class="text-gray-700 dark:text-gray-200"></span>
                                    <span x-show="slot.substitute" class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500 dark:bg-white/10">sostituto</span>
                                    <span x-show="! slot.brush" class="text-gray-400">non ce l'hai</span>
                                </td>
                                <td class="hidden px-4 py-3 align-top text-xs text-gray-500 sm:table-cell" x-text="slot.description"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </x-card>
        </div>

        <div>
            <x-section-header>Come trattarli</x-section-header>
            <x-card>
                <ul class="list-disc space-y-2 pl-5 text-sm text-gray-700 dark:text-gray-300">
                    <li>I metallici consumano la punta. Il tuo kit ha un solo tondo per misura: se puoi, compra un tondo 1 economico da usare <b>solo per i metallici</b> e aggiungilo qui con la spunta «solo metallici». Altrimenti lava molto bene il pennello subito dopo il metallo.</li>
                    <li>Lo <b>Spot Detail 20/0</b> e il <b>Tondo 10/0</b> sono delicatissimi: usali solo per pupille, riflessi e puntini, mai per stendere basi.</li>
                    <li>I <b>drybrush</b> si rovinano per natura: usali solo per il drybrush, mai per stendere colore liquido.</li>
                    <li>Non intingere mai fino alla ghiera; sciacqua spesso e non lasciarli in piedi nell'acqua.</li>
                    <li>A fine sessione lavali con acqua tiepida e sapone, rifai la punta con le dita e rimetti il cappuccio.</li>
                    <li>I tondi più fini (10/0, 5/0) tengono pochissimo colore: si asciugano in fretta, quindi ricaricali spesso.</li>
                </ul>
            </x-card>
        </div>
    </div>
</x-app-layout>
