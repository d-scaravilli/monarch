@php
    $accent = \App\Support\ModuleTheme::classes($currentModule?->color);
    $pathPct = $path['total'] ? round($path['done'] / $path['total'] * 100) : 0;
@endphp

<x-app-layout>
    <x-slot name="header">Home</x-slot>

    <div class="space-y-6">
        <x-card>
            <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Pennello &amp; Resina</h2>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Il tuo laboratorio per dipingere a pennello le stampe in resina, anche partendo da zero. Ricette in gocce fatte
                con i colori che hai, un percorso guidato e schede pronte per ogni progetto.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('resina.path.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-white {{ $accent['badge'] }}">
                    {{ $path['done'] ? 'Continua il percorso' : 'Inizia il percorso' }}
                </a>
                <a href="{{ route('resina.projects.index') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">Apri un progetto</a>
                <a href="{{ route('resina.finder') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">Trova un colore</a>
            </div>
        </x-card>

        @if ($missingReferences)
            <a href="{{ route('resina.references.index') }}" class="flex items-center justify-between gap-3 rounded-2xl px-4 py-2.5 text-sm text-gray-500 ring-1 ring-gray-100 hover:bg-white dark:text-gray-400 dark:ring-white/10 dark:hover:bg-white/5">
                <span class="flex items-center gap-2"><x-heroicon-o-photo class="h-4 w-4" /> {{ $missingReferences }} foto di riferimento da aggiungere</span>
                <span class="text-xs font-medium">Completa →</span>
            </a>
        @endif

        <div>
            <x-section-header>La tua mensola</x-section-header>
            <x-card class="space-y-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $paints->flatten()->count() }} flaconi e {{ $brushCount }} pennelli. Tocca un colore per metterlo nel mixer.
                </p>

                @foreach ($paints as $line => $linePaints)
                    <div>
                        <p class="mb-2 text-xs font-medium text-gray-400 dark:text-gray-500">{{ $line }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($linePaints as $userPaint)
                                @php
                                    $name = $userPaint->paint?->name ?? $userPaint->name;
                                    $code = $userPaint->paint?->code ?? $userPaint->code;
                                    $key = $userPaint->paint_id ? 'p'.$userPaint->paint_id : 'u'.$userPaint->id;
                                @endphp
                                <a href="{{ route('resina.mixer', ['add' => $key]) }}" title="{{ $name }} {{ $code }}"
                                   class="inline-flex items-center gap-2 rounded-xl bg-gray-50 py-1.5 pl-1.5 pr-3 hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10">
                                    <x-resina.swatch :hex="$userPaint->paint?->hex ?? $userPaint->hex" :type="$userPaint->paint?->type ?? $userPaint->type" />
                                    <span class="leading-tight">
                                        <span class="block text-xs font-medium text-gray-900 dark:text-gray-100">{{ $name }}</span>
                                        <span class="block text-[11px] text-gray-400">{{ $code }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </x-card>
        </div>

        <div>
            <x-section-header>Da dove partire</x-section-header>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('resina.path.index') }}">
                    <x-card class="h-full transition hover:ring-gray-300 dark:hover:ring-white/20">
                        <div class="flex items-start gap-4">
                            <x-module-badge icon="map" :color="$currentModule?->color" size="h-10 w-10" />
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-gray-100">Percorso principiante</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $path['done'] }} di {{ $path['total'] }} passi fatti.
                                    {{ $path['next'] ? 'Prossimo: '.$path['next']->title.'.' : 'Completato!' }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <div class="h-full rounded-full {{ $accent['badge'] }}" style="width: {{ $pathPct }}%"></div>
                        </div>
                    </x-card>
                </a>
                <x-resina.tool-card :href="route('resina.techniques.index')" icon="light-bulb" title="Tecniche">
                    Diluire, base-ombra-luce, wash, drybrush, metallici, occhi.
                </x-resina.tool-card>
                <x-resina.tool-card :href="route('resina.recipes.index')" icon="book-open" title="Ricettario">
                    {{ $recipeCount }} ricette in gocce: pelle, capelli, metalli, tessuti, basette.
                </x-resina.tool-card>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-section-header>Progetti</x-section-header>
                <a href="{{ route('resina.projects.index') }}" class="mb-2 text-xs font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400">Tutti i progetti</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    <x-resina.project-card :project="$project" />
                @endforeach
                <x-resina.tool-card :href="route('resina.figures.index')" icon="plus" title="Un'altra figura?">
                    Crea la tua scheda in «Le mie figure»: scegli le zone e i colori, le ricette si calcolano da sole.
                </x-resina.tool-card>
            </div>
        </div>

        <div>
            <x-section-header>Strumenti</x-section-header>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-resina.tool-card :href="route('resina.photo.create')" icon="camera" title="Analizza una foto">Carica un'immagine, scegli il personaggio e ottieni la guida con colori e pennelli.</x-resina.tool-card>
                <x-resina.tool-card :href="route('resina.brushes.index')" icon="paint-brush" title="I miei pennelli">Il tuo kit e a cosa serve ogni pennello.</x-resina.tool-card>
                <x-resina.tool-card :href="route('resina.finder')" icon="eye-dropper" title="Trova un colore">Scegli un colore, ti dico come ottenerlo.</x-resina.tool-card>
                <x-resina.tool-card :href="route('resina.mixer')" icon="beaker" title="Mixer">Prova le miscele prima di sprecare colore.</x-resina.tool-card>
                <x-resina.tool-card :href="route('resina.figures.index')" icon="user-circle" title="Le mie figure">Crea la scheda colori di qualsiasi figura.</x-resina.tool-card>
                <x-resina.tool-card :href="route('resina.paints.index')" icon="swatch" title="I miei colori">Inventario, codici e aggiunta di nuovi flaconi.</x-resina.tool-card>
                <x-resina.tool-card :href="route('resina.shop.index')" icon="shopping-bag" title="Da comprare">Cosa ti manca, in ordine di importanza.</x-resina.tool-card>
                <x-resina.tool-card :href="route('resina.tutorials.index')" icon="play-circle" title="Tutorial video">Ricerche YouTube già pronte.</x-resina.tool-card>
            </div>
        </div>
    </div>
</x-app-layout>
