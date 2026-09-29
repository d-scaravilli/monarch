<x-app-layout>
    <x-slot name="header">Modifica i tutorial</x-slot>

    <x-resina.payload id="resina-tutorial-rows" :data="$rows" />

    <div class="max-w-3xl space-y-4">
        <a href="{{ route('resina.tutorials.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> Tutorial video
        </a>

        <x-resina.form-errors />

        <form method="POST" action="{{ route('resina.tutorials.update') }}" class="space-y-3"
              x-data="resinaRows('resina-tutorial-rows', { id: '', title: '', query: '', description: '' })">
            @csrf @method('PUT')

            <template x-for="(row, index) in rows" :key="row.uid">
                <x-card class="flex items-start gap-2">
                    <input type="hidden" :name="`tutorials[${index}][id]`" :value="row.id">
                    <div class="grid min-w-0 flex-1 gap-2 sm:grid-cols-2">
                        <input type="text" :name="`tutorials[${index}][title]`" x-model="row.title" required placeholder="Titolo"
                               class="rounded-xl border-gray-200 bg-gray-50 text-sm font-medium dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <input type="text" :name="`tutorials[${index}][query]`" x-model="row.query" required placeholder="Ricerca su YouTube"
                               class="rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <input type="text" :name="`tutorials[${index}][description]`" x-model="row.description" placeholder="Descrizione"
                               class="rounded-xl border-gray-200 bg-gray-50 text-sm sm:col-span-2 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    </div>
                    <x-resina.row-buttons />
                </x-card>
            </template>

            <button type="button" @click="add()" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi tutorial
            </button>

            <div class="flex items-center gap-3">
                <x-primary-button>Salva i tutorial</x-primary-button>
                <a href="{{ route('resina.tutorials.index') }}" class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400">Annulla</a>
            </div>
        </form>
    </div>
</x-app-layout>
