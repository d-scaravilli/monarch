@php $accent = \App\Support\ModuleTheme::classes($currentModule?->color); @endphp

<x-app-layout>
    <x-slot name="header">Ricettario</x-slot>

    <x-resina.payload id="resina-recipes" :data="$payload" />

    <div x-data="resinaRecipeBook('resina-recipes')" class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Ricette generiche valide per qualsiasi progetto, divise in ombra, base e luce. Una goccia dal flacone vale
                una parte; il cerchio mostra il colore previsto con il suo codice esadecimale.
            </p>
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('resina.recipes.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">
                    <x-heroicon-o-plus class="h-4 w-4" /> Nuova ricetta
                </a>
            @endif
        </div>

        <div class="relative">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input type="search" x-model.debounce.200ms="query" placeholder="Cerca: pelle, oro, viola, roccia, Shun…"
                   class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:border-gray-900 focus:bg-white focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
        </div>

        <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <template x-for="c in categories" :key="c.slug">
                <button type="button" @click="category = c.slug"
                        class="shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium transition"
                        :class="category === c.slug ? '{{ $accent['badge'] }} text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10'"
                        x-text="c.name"></button>
            </template>
        </div>

        <template x-for="r in recipes" :key="r.slug">
            <x-resina.recipe-card x-show="visible(r)" />
        </template>

        <p x-show="visibleCount === 0" x-cloak class="py-8 text-center text-sm text-gray-500">Nessuna ricetta trovata.</p>
    </div>
</x-app-layout>
