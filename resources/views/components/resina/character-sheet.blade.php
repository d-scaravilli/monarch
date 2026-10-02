@props(['payloadId', 'title', 'subtitle' => null])
@php $accent = \App\Support\ModuleTheme::classes($currentModule?->color); @endphp

{{--
    The tabbed character sheet (resinaCharacterSheet in resources/js/resina):
    catalog characters and personal figures alike. `top` holds the back link
    and actions; the default slot goes under the title (the figure editor).
--}}
<div x-data="resinaCharacterSheet({{ Illuminate\Support\Js::from($payloadId) }})" class="space-y-4">
    {{ $top }}

    <div>
        <h2 class="text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl dark:text-gray-100">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
        @endif
    </div>

    {{ $slot }}

    <p x-show="error" x-cloak x-text="error" class="rounded-xl bg-red-50 px-4 py-2.5 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400"></p>

    {{-- Version picker, above the big palette (hidden when there's only one). --}}
    <template x-if="versions.length > 1">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">Versione della <span x-text="armorLabelLower"></span>:</span>
            <div x-ref="versionStrip" class="relative -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <template x-for="v in versions" :key="v.id">
                    <button type="button" @click="chooseVersion(v.id)" :data-active="versionId === v.id"
                            class="shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium transition"
                            :class="versionId === v.id ? '{{ $accent['badge'] }} text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10'">
                        <span x-text="v.label"></span>
                        <small x-show="v.subtitle" class="font-normal opacity-75" x-text="v.subtitle"></small>
                    </button>
                </template>
            </div>
        </div>
    </template>

    {{-- The chosen version's reference photo. --}}
    <template x-if="version">
        <div>
            <template x-if="version.photo">
                <a :href="version.photo.original" target="_blank" rel="noopener" class="inline-flex items-end gap-3" title="Apri la foto">
                    <img :src="version.photo.thumb" alt="Foto di riferimento" class="h-40 max-w-full rounded-2xl object-cover ring-1 ring-black/10 dark:ring-white/10">
                    <span class="pb-1 text-xs text-gray-500 dark:text-gray-400">
                        Foto di riferimento<template x-if="version.source"><span> · <span x-text="version.source"></span></span></template>
                    </span>
                </a>
            </template>
            <template x-if="! version.photo">
                <div class="flex flex-wrap items-center gap-3 rounded-2xl border-2 border-dashed border-gray-200 px-4 py-3 dark:border-white/15">
                    <x-heroicon-o-photo class="h-6 w-6 shrink-0 text-gray-400" />
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-medium text-gray-700 dark:text-gray-200">Manca la foto di riferimento</p>
                        <p x-show="! canUploadReferences" class="text-gray-500 dark:text-gray-400">L'admin non l'ha ancora aggiunta.</p>
                    </div>
                    <button type="button" x-show="canUploadReferences" @click="$dispatch('resina-reference-open', version)"
                            class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white {{ $accent['badge'] }}">Carica la foto</button>
                </div>
            </template>
        </div>
    </template>

    {{-- The big palette: one block per zone. --}}
    <div data-big-palette class="grid grid-cols-2 gap-1.5 overflow-hidden rounded-2xl sm:grid-cols-3 lg:grid-cols-4">
        <template x-for="z in bigPalette" :key="z.key">
            <div class="flex min-h-16 items-end p-2.5 text-xs font-semibold" :style="z.style" x-text="z.name"></div>
        </template>
    </div>

    {{-- Tabs in painting order, stuck to the top while scrolling. --}}
    <div x-ref="tabsAnchor"></div>
    <nav class="sticky top-[calc(max(1rem,env(safe-area-inset-top))+3rem)] z-10 -mx-4 bg-gray-50/95 px-4 py-2 backdrop-blur sm:-mx-6 sm:px-6 lg:top-0 lg:-mx-10 lg:px-10 dark:bg-gray-950/95">
        <div x-ref="tabStrip" class="relative flex gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <template x-for="t in tabs" :key="t.key">
                <button type="button" @click="setTab(t.key)" :data-active="currentTab.key === t.key"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium transition"
                        :class="currentTab.key === t.key ? '{{ $accent['badge'] }} text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5'">
                    <span x-text="t.label"></span>
                    <template x-if="t.key !== 'panoramica' && t.key !== 'reference' && stats(t.key)[1]">
                        <small class="rounded-full px-1.5 py-px text-[11px] font-semibold"
                               :class="stats(t.key)[0] === stats(t.key)[1]
                                    ? 'bg-green-500 text-white'
                                    : (currentTab.key === t.key ? 'bg-white/25' : 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400')"
                               x-text="stats(t.key)[0] + '/' + stats(t.key)[1]"></small>
                    </template>
                </button>
            </template>
        </div>
    </nav>

    {{-- Panoramica --}}
    <div x-show="currentTab.key === 'panoramica'" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <x-card>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Il tuo avanzamento</h3>
                <div class="my-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    <div class="h-full rounded-full {{ $accent['badge'] }} transition-all" :style="'width:' + overall.pct + '%'"></div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <span x-text="overall.done"></span> di <span x-text="overall.total"></span> passaggi fatti (esclusi i facoltativi e la
                    basetta). Spunta «fatto» sotto ogni passaggio mentre dipingi.
                </p>
            </x-card>
            <x-card>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Come usare questa scheda</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Le schede in alto sono già nell'<b>ordine in cui dipingere</b>: da sinistra a destra. Dentro ogni scheda i passaggi
                    sono numerati e vanno fatti in sequenza. Il numero accanto alla scheda indica i passaggi fatti.
                </p>
            </x-card>
        </div>

        <template x-if="character.versions_note">
            <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                <b>Versioni.</b> <span x-text="character.versions_note"></span>
            </div>
        </template>
        <template x-if="version && version.note">
            <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                <b x-text="version.label + (version.subtitle ? ' · ' + version.subtitle : '') + '.'"></b> <span x-text="version.note"></span>
            </div>
        </template>

        <div>
            <x-section-header>Ordine di lavoro</x-section-header>
            <x-card>
                <ol class="space-y-3">
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-gray-300 dark:bg-white/20"></span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Preparazione e primer nero</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Lavaggio, levigatura e primer. Poi sottofondo di Bianco Osso sulle zone chiare.</p>
                            <a x-show="urls.techniques" :href="urls.techniques + '#t-prep'" class="text-xs font-medium {{ $accent['text'] }}">Come si fa</a>
                        </div>
                    </li>
                    <template x-for="step in workOrder" :key="step.key">
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" :class="step.total && step.done === step.total ? 'bg-green-500' : 'bg-gray-300 dark:bg-white/20'"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="step.label"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="step.zones"></p>
                                <button type="button" @click="setTab(step.key)" class="text-xs font-medium {{ $accent['text'] }}"
                                        x-text="'Apri · ' + step.done + '/' + step.total + ' passaggi'"></button>
                            </div>
                        </li>
                    </template>
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-gray-300 dark:bg-white/20"></span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Vernice</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Opaca su pelle e stoffa, lucida o satinata (o niente) sui metalli. Dopo 24 ore.</p>
                            <a x-show="urls.techniques" :href="urls.techniques + '#t-vernice'" class="text-xs font-medium {{ $accent['text'] }}">Come si fa</a>
                        </div>
                    </li>
                </ol>
            </x-card>
        </div>

        <div>
            <x-section-header>Colori che userai (<span x-text="paintsUsed.length"></span>)</x-section-header>
            <div class="flex flex-wrap gap-2">
                <template x-for="p in paintsUsed" :key="p.key">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white py-1 pl-1 pr-3 text-sm shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10">
                        <span class="h-5 w-5 rounded-full ring-1 ring-black/10" :style="p.style"></span>
                        <span class="text-gray-800 dark:text-gray-100"><span x-text="p.name"></span> <small class="text-[11px] text-gray-400" x-text="p.code"></small></span>
                    </span>
                </template>
            </div>
        </div>

        <div>
            <x-section-header>Pennelli che userai (<span x-text="brushesUsed.length"></span>)</x-section-header>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="b in brushesUsed" :key="b.key">
                    <x-card class="p-4">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            🖌 <span x-text="b.label"></span>
                            <span x-show="b.missing" class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 dark:bg-white/10">non ce l'hai</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="b.description"></p>
                        <p class="mt-1 text-xs text-gray-700 dark:text-gray-300" x-text="b.zones"></p>
                    </x-card>
                </template>
            </div>
            <p class="mt-2 text-xs text-gray-400">
                Le misure vengono dall'elenco in <a href="{{ route('resina.brushes.index') }}" class="underline">I miei pennelli</a>. Tieni un pennello tondo medio solo per i metallici.
            </p>
        </div>

        <div>
            <x-section-header>Oltre ai colori ti serve</x-section-header>
            <x-card>
                <ul class="list-disc space-y-1.5 pl-5 text-sm text-gray-700 dark:text-gray-300">
                    <li>Tavolozza bagnata per i colori normali, un <b>piattino</b> a parte per i metallici.</li>
                    <li>Un bicchiere d'acqua per sciacquare, carta assorbente, stuzzicadenti per mescolare.</li>
                    <li>Un supporto per tenere la figura senza toccarla.</li>
                    <li>Lampada a luce bianca e, alla fine, la vernice.</li>
                </ul>
            </x-card>
        </div>

        <template x-if="character.tips.length">
            <div>
                <x-section-header>Consigli per <span x-text="character.name"></span></x-section-header>
                <x-card>
                    <ul class="list-disc space-y-1.5 pl-5 text-sm text-gray-700 dark:text-gray-300">
                        <template x-for="(tip, n) in character.tips" :key="n">
                            <li x-text="tip"></li>
                        </template>
                    </ul>
                </x-card>
            </div>
        </template>

        <div class="flex flex-wrap gap-2">
            <template x-if="copyUrl">
                <form method="POST" :action="copyUrl">
                    @csrf
                    <button type="submit" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                        Copia nelle mie figure per personalizzarla
                    </button>
                </form>
            </template>
            <button type="button" @click="resetDone()"
                    class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                Azzera i «fatto»
            </button>
        </div>
    </div>

    {{-- Reference --}}
    <div x-show="currentTab.key === 'reference'" x-cloak class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Prima di dipingere cerca un'immagine della <b>versione esatta</b> della tua stampa: anime, manga e figure hanno spesso
            colori diversi.
        </p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="link in referenceLinks" :key="link.url">
                <a :href="link.url" target="_blank" rel="noopener">
                    <x-card class="h-full transition hover:ring-gray-300 dark:hover:ring-white/20">
                        <p class="font-medium text-gray-900 dark:text-gray-100" x-text="link.title"></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="link.description"></p>
                    </x-card>
                </a>
            </template>
        </div>
        <template x-if="versions.length > 1">
            <div>
                <x-section-header>Reference per versione</x-section-header>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="link in versionLinks" :key="link.url">
                        <a :href="link.url" target="_blank" rel="noopener">
                            <x-card class="h-full transition hover:ring-gray-300 dark:hover:ring-white/20">
                                <p class="font-medium text-gray-900 dark:text-gray-100" x-text="link.label"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="link.subtitle"></p>
                            </x-card>
                        </a>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Painting tabs: the zones, their steps and the "fatto" boxes. --}}
    <template x-if="currentTab.key !== 'panoramica' && currentTab.key !== 'reference'">
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-300" x-text="intro(currentTab.key)"></p>

            <template x-if="currentTab.key === 'armatura' && version && version.note">
                <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                    <b x-text="version.label + (version.subtitle ? ' · ' + version.subtitle : '') + '.'"></b> <span x-text="version.note"></span>
                </div>
            </template>

            <template x-for="zone in zonesOf(currentTab.key)" :key="zone.key">
                <article x-data="{ get view() { return zoneView(zone) } }"
                         class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10">
                    <header class="flex flex-wrap items-center gap-3">
                        <span class="h-11 w-11 shrink-0 rounded-xl ring-1 ring-black/10 dark:ring-white/10" :style="view.swatch"></span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100" x-text="view.name"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <template x-if="view.recipeLink">
                                    <span>Ricetta: <a :href="view.recipeLink.href" class="font-medium underline-offset-2 hover:underline {{ $accent['text'] }}" x-text="view.recipeLink.title"></a></span>
                                </template>
                                <span x-show="view.computed">Ricetta calcolata dai tuoi colori</span>
                            </p>
                        </div>
                        <template x-if="view.scale.length">
                            <div>
                                <p class="mb-1 text-[11px] text-gray-400">Toni</p>
                                <div class="inline-flex overflow-hidden rounded-lg ring-1 ring-black/10 dark:ring-white/10">
                                    <template x-for="(tone, t) in view.scale" :key="t">
                                        <span class="h-6 w-6" :style="tone.style" :title="tone.title"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </header>

                    <p x-show="view.note" x-text="view.note" class="mt-3 rounded-xl bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"></p>

                    <template x-if="view.target">
                        <div class="mt-3 space-y-2">
                            <p class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span class="h-4 w-4 rounded-full ring-1 ring-black/10" :style="'background:' + view.target.hex"></span>
                                Colore di riferimento <code x-text="view.target.hex"></code> · base della ricetta
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                      :class="{ ok: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400', mid: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400', far: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' }[view.target.closeness[0]]"
                                      x-text="view.target.closeness[1]"></span>
                            </p>
                            <template x-if="view.target.buy">
                                <div class="flex items-start gap-3 rounded-xl bg-gray-50 p-3 text-sm dark:bg-white/5">
                                    <span class="h-8 w-8 shrink-0 rounded-lg ring-1 ring-black/10" :style="'background:' + view.target.buy.hex"></span>
                                    <div class="min-w-0 text-gray-700 dark:text-gray-200">
                                        <b>Per il colore esatto:</b> Vallejo Game Color <span x-text="view.target.buy.name"></span>
                                        <small class="text-[11px] text-gray-400" x-text="view.target.buy.code"></small>.
                                        <span x-text="view.target.buy.why"></span>
                                        <form method="POST" :action="view.target.buy.buyUrl" class="mt-2">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium hover:bg-white dark:border-white/10 dark:hover:bg-white/10">L'ho comprato</button>
                                        </form>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <p x-show="view.tip" x-text="view.tip" class="mt-3 rounded-xl bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"></p>
                    <p x-show="view.info" x-text="view.info" class="mt-3 text-xs text-gray-400"></p>
                    <p x-show="view.pending" class="mt-3 flex items-center gap-2 text-sm text-gray-400">
                        <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" /> Calcolo della ricetta con i tuoi colori…
                    </p>

                    <div class="divide-y divide-gray-100 dark:divide-white/10">
                        <template x-for="(s, i) in view.steps" :key="i">
                            <div :class="done[doneKey(zone, i)] && 'bg-green-50/60 dark:bg-green-500/5 -mx-2 px-2 rounded-xl'">
                                <x-resina.step-row>
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-200 dark:text-gray-300 dark:ring-white/10"
                                           :class="done[doneKey(zone, i)] && 'bg-green-500 text-white ring-green-500 dark:text-white'">
                                        <input type="checkbox" class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                               :checked="!! done[doneKey(zone, i)]" @change="toggle(zone, i, $event.target.checked)">
                                        fatto
                                    </label>
                                </x-resina.step-row>
                            </div>
                        </template>
                    </div>
                </article>
            </template>

            <div class="flex justify-end" x-show="nextTab">
                <button type="button" @click="setTab(nextTab.key)"
                        class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-semibold text-white {{ $accent['badge'] }}">
                    Avanti: <span x-text="nextTab?.label"></span> →
                </button>
            </div>
        </div>
    </template>
</div>
