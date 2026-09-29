<x-app-layout>
    <x-slot name="header">Home</x-slot>

    <div class="space-y-6">
        <x-card>
            <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Pennello &amp; Resina</h2>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Il tuo laboratorio per dipingere a pennello le stampe in resina, anche partendo da zero. Ricette in gocce fatte
                con i colori che hai, un percorso guidato e schede pronte per ogni progetto.
            </p>
        </x-card>

        <div>
            <x-section-header>La tua mensola</x-section-header>
            <x-card class="space-y-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $paints->flatten()->count() }} flaconi e {{ $brushCount }} pennelli. Tocca un flacone per vedere a cosa serve.
                </p>

                @foreach ($paints as $line => $linePaints)
                    <div>
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-2">{{ $line }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($linePaints as $userPaint)
                                @php
                                    $name = $userPaint->paint?->name ?? $userPaint->name;
                                    $code = $userPaint->paint?->code ?? $userPaint->code;
                                    $hex = $userPaint->paint?->hex ?? $userPaint->hex;
                                @endphp
                                <a href="{{ route('resina.paints.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-gray-50 py-1.5 pl-1.5 pr-3 hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10" title="{{ $name }} {{ $code }}">
                                    <x-resina.swatch :hex="$hex" :type="$userPaint->paint?->type ?? $userPaint->type" />
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
            <x-section-header>Strumenti</x-section-header>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['resina.recipes.index', 'book-open', 'Ricettario', 'Ricette in gocce: pelle, capelli, metalli, tessuti, basette.'],
                    ['resina.paints.index', 'swatch', 'I miei colori', 'Inventario, codici e aggiunta di nuovi flaconi.'],
                    ['resina.brushes.index', 'paint-brush', 'I miei pennelli', 'Il tuo kit e a cosa serve ogni pennello.'],
                ] as [$route, $icon, $title, $text])
                    <a href="{{ route($route) }}">
                        <x-card class="flex h-full items-start gap-4 transition hover:ring-gray-300 dark:hover:ring-white/20">
                            <x-module-badge :icon="$icon" :color="$currentModule?->color" size="h-10 w-10" />
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $text }}</p>
                            </div>
                        </x-card>
                    </a>
                @endforeach
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
            </div>
        </div>
    </div>
</x-app-layout>
