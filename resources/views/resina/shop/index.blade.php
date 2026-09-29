<x-app-layout>
    <x-slot name="header">Da comprare</x-slot>

    <x-resina.payload id="resina-shop" :data="$payload" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Hai già una base ottima. Con i due set TMM hai anche i wash per oro e argento (gli Shade). Queste sono le cose che ti
                mancano, in ordine di importanza.
            </p>
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('resina.shop.edit') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                    <x-heroicon-o-pencil-square class="h-4 w-4" /> Modifica i colori consigliati
                </a>
            @endif
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-card class="resina-prose">
                <h3>Indispensabili</h3>
                <ul>
                    <li><b>Un wash seppia</b> per pelle, cuoio e capelli: Vallejo Game Color Wash Sepia <span class="code">73.200</span>.</li>
                    <li><b>Un wash nero</b> per rocce, catene e dettagli scuri: Game Color Wash Black <span class="code">73.201</span>. Per le rocce funziona anche lo Sterling Silver Shade che hai già.</li>
                    <li><b>Vernice</b>: una opaca (pelle, stoffa) e una lucida o satinata (metalli, gemme, visori).</li>
                    <li><b>Un pennello piatto economico</b> per il drybrush e un pennello vecchio per mescolare.</li>
                    <li><b>Guanti in nitrile e mascherina</b> per levigare la resina.</li>
                </ul>
            </x-card>
            <x-card class="resina-prose">
                <h3>Molto utili</h3>
                <ul>
                    <li><b>Glaze Medium Vallejo</b>: velature e wash fatti in casa senza aloni.</li>
                    <li><b>Wash Flesh</b> <span class="code">73.204</span>: ombre della pelle in un solo passaggio.</li>
                    <li><b>Un supporto</b> per tenere la figura senza toccarla: tappo di sughero o barattolo con un po' di Patafix.</li>
                    <li><b>Una lampada a luce bianca</b> (4000–5000 K).</li>
                    <li><b>Sapone per pennelli</b>, per farli durare.</li>
                    <li><b>Flaconi contagocce vuoti</b> per conservare i mix che usi spesso.</li>
                    <li><b>Un primer grigio o bianco a bomboletta</b>, per figure con tanta pelle o armature chiare (Cigno, Andromeda, Ranger Giallo).</li>
                </ul>
            </x-card>
        </div>

        <div x-data="resinaShopSuggestions('resina-shop')">
            <x-section-header>Colori che non hai</x-section-header>
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
                                        <x-resina.closeness value="s.best.closeness" />
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
