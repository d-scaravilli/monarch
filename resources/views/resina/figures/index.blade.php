<x-app-layout>
    <x-slot name="header">Le mie figure</x-slot>

    <x-resina.payload id="resina-figures" :data="$payload" />

    <div x-data="resinaFigureList('resina-figures')" class="space-y-4">
        <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Crea la scheda colori di qualsiasi figura, anche di progetti che qui non ci sono. Per ogni zona scegli una ricetta del
            ricettario, oppure un colore libero: la ricetta con ombra, base e luce si calcola da sola con i tuoi colori.
        </p>

        <x-card>
            <form method="POST" action="{{ route('resina.figures.store') }}" class="flex flex-wrap gap-2">
                @csrf
                <x-text-input name="name" value="{{ old('name') }}" placeholder="Nome della figura (es. Goku, Batman…)" class="min-w-0 flex-1 basis-56" required />
                <x-primary-button>Crea scheda</x-primary-button>
                <a href="{{ route('resina.photo.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                    <x-heroicon-o-camera class="h-4 w-4" /> Da una foto
                </a>
            </form>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </x-card>

        @if ($figures->isEmpty())
            <p class="text-sm text-gray-500">
                Non hai ancora creato schede. Puoi anche partire da un personaggio esistente con «Copia nelle mie figure», o da una
                foto con «Analizza una foto».
            </p>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($figures as $figure)
                    <a href="{{ route('resina.figures.show', $figure) }}"
                       class="block overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:ring-gray-300 dark:bg-gray-900 dark:ring-white/10 dark:hover:ring-white/20">
                        @if ($figure->reference_thumb_path)
                            <img src="{{ route('resina.figures.image', [$figure, 'miniatura']) }}" alt="" loading="lazy" class="h-32 w-full object-cover">
                        @endif
                        <div class="flex h-10">
                            <template x-for="z in palettes[{{ $figure->id }}]" :key="z.key">
                                <span class="flex-1" :style="z.style" :title="z.name"></span>
                            </template>
                        </div>
                        <div class="p-3">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $figure->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $figure->zones->count() }} zone{{ $figure->source === 'foto' ? ' · dalla foto' : '' }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
