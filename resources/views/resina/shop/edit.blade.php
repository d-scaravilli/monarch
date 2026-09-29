<x-app-layout>
    <x-slot name="header">Modifica i colori consigliati</x-slot>

    <x-resina.payload id="resina-shop-rows" :data="$rows" />

    <div class="max-w-3xl space-y-4">
        <a href="{{ route('resina.shop.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> Da comprare
        </a>

        <x-resina.form-errors />

        <form method="POST" action="{{ route('resina.shop.update') }}" class="space-y-3"
              x-data="resinaRows('resina-shop-rows', { id: '', code: '', name: '', hex: '#888888', why: '' })">
            @csrf @method('PUT')

            <template x-for="(row, index) in rows" :key="row.uid">
                <x-card class="flex items-start gap-2">
                    <input type="hidden" :name="`suggestions[${index}][id]`" :value="row.id">
                    <input type="color" :name="`suggestions[${index}][hex]`" x-model="row.hex" aria-label="Colore"
                           class="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-white/10 dark:bg-white/5">
                    <div class="grid min-w-0 flex-1 gap-2 sm:grid-cols-[1fr_8rem]">
                        <input type="text" :name="`suggestions[${index}][name]`" x-model="row.name" required placeholder="Nome (es. Royal Purple)"
                               class="rounded-xl border-gray-200 bg-gray-50 text-sm font-medium dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <input type="text" :name="`suggestions[${index}][code]`" x-model="row.code" required placeholder="Codice"
                               class="rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <input type="text" :name="`suggestions[${index}][why]`" x-model="row.why" placeholder="Perché serve"
                               class="rounded-xl border-gray-200 bg-gray-50 text-sm sm:col-span-2 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    </div>
                    <x-resina.row-buttons />
                </x-card>
            </template>

            <button type="button" @click="add()" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi colore
            </button>

            <p class="text-xs text-gray-400">Chi ha già premuto «L'ho comprato» tiene il colore tra i suoi, anche se qui lo togli.</p>

            <div class="flex items-center gap-3">
                <x-primary-button>Salva</x-primary-button>
                <a href="{{ route('resina.shop.index') }}" class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400">Annulla</a>
            </div>
        </form>
    </div>
</x-app-layout>
