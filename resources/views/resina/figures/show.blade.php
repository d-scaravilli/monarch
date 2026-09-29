<x-app-layout>
    <x-slot name="header">{{ $figure->name }}</x-slot>

    <x-resina.payload id="resina-character" :data="$payload" />

    <x-resina.character-sheet payload-id="resina-character" :title="$figure->name" :subtitle="$figure->source === 'foto' ? 'Guida creata da una foto' : 'Scheda personale'">
        <x-slot:top>
            <a href="{{ route('resina.figures.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                <x-heroicon-o-arrow-left class="h-4 w-4" /> Le mie figure
            </a>
        </x-slot:top>

        @if ($figure->reference_thumb_path || $figure->note)
            <div class="flex flex-wrap items-start gap-4">
                @if ($figure->reference_thumb_path)
                    <a href="{{ route('resina.figures.image', [$figure, 'originale']) }}" target="_blank" rel="noopener" class="shrink-0" title="Apri la foto">
                        <img src="{{ route('resina.figures.image', [$figure, 'miniatura']) }}" alt="Immagine di riferimento"
                             class="h-32 w-32 rounded-2xl object-cover ring-1 ring-black/10 dark:ring-white/10">
                    </a>
                @endif
                @if ($figure->note)
                    <p class="min-w-0 flex-1 basis-60 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">{{ $figure->note }}</p>
                @endif
            </div>
        @endif

        <details class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10" @if ($openEditor) open @endif>
            <summary class="flex cursor-pointer items-center justify-between gap-2 px-5 py-4 text-sm">
                <span><b class="text-gray-900 dark:text-gray-100">Modifica nome e zone</b> <span class="text-gray-500">({{ $figure->zones->count() }} zone)</span></span>
                <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-400" />
            </summary>
            <div class="space-y-4 border-t border-gray-100 p-5 dark:border-white/10">
                <x-resina.form-errors />

                <form method="POST" action="{{ route('resina.figures.update', $figure) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div class="space-y-1.5">
                        <x-input-label for="figure-name" value="Nome" />
                        <x-text-input id="figure-name" name="name" value="{{ old('name', $figure->name) }}" class="w-full font-semibold" required />
                    </div>
                    <x-resina.zone-editor :payload="$zoneEditor" />
                    <x-primary-button>Salva la scheda</x-primary-button>
                </form>

                <x-resina.delete-card :action="route('resina.figures.destroy', $figure)" title="Elimina scheda" confirm="Eliminare questa scheda?{{ $figure->reference_image_path ? ' Anche la foto verrà cancellata.' : '' }}">
                    Cancella la figura con le sue zone e i «fatto»{{ $figure->reference_image_path ? ', e la foto di riferimento' : '' }}.
                </x-resina.delete-card>
            </div>
        </details>
    </x-resina.character-sheet>
</x-app-layout>
