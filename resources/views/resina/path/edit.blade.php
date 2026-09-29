<x-app-layout>
    <x-slot name="header">Modifica il percorso</x-slot>

    <x-resina.payload id="resina-path-rows" :data="$rows" />

    <div class="max-w-3xl space-y-4">
        <a href="{{ route('resina.path.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> Percorso principiante
        </a>

        <x-resina.form-errors />

        <form method="POST" action="{{ route('resina.path.update') }}" class="space-y-3"
              x-data="resinaRows('resina-path-rows', { id: '', title: '', description: '', link_route: '', link_anchor: '' })">
            @csrf @method('PUT')

            <template x-for="(row, index) in rows" :key="row.uid">
                <x-card class="space-y-2">
                    <input type="hidden" :name="`steps[${index}][id]`" :value="row.id">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold dark:bg-white/10" x-text="index + 1"></span>
                        <input type="text" :name="`steps[${index}][title]`" x-model="row.title" required placeholder="Titolo del passo"
                               class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm font-medium dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <x-resina.row-buttons />
                    </div>
                    <textarea :name="`steps[${index}][description]`" x-model="row.description" rows="2" placeholder="Cosa fare"
                              class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100"></textarea>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <select :name="`steps[${index}][link_route]`" x-model="row.link_route" aria-label="Pagina di «Come si fa»"
                                class="rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="">Nessun link «Come si fa»</option>
                            @foreach ($routes as $route => $label)
                                <option value="{{ $route }}" :selected="row.link_route === '{{ $route }}'">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select :name="`steps[${index}][link_anchor]`" x-model="row.link_anchor" x-show="row.link_route === 'resina.techniques.index'" aria-label="Tecnica"
                                class="rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="">Inizio della pagina</option>
                            @foreach ($anchors as $anchor => $label)
                                <option value="{{ $anchor }}" :selected="row.link_anchor === '{{ $anchor }}'">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-card>
            </template>

            <button type="button" @click="add()" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi passo
            </button>

            <p class="text-xs text-gray-400">Togliere un passo cancella anche le spunte che gli utenti avevano su di lui.</p>

            <div class="flex items-center gap-3">
                <x-primary-button>Salva il percorso</x-primary-button>
                <a href="{{ route('resina.path.index') }}" class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400">Annulla</a>
            </div>
        </form>
    </div>
</x-app-layout>
