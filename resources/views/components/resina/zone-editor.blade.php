@props(['payload'])

{{--
    Zone list editor (resinaZoneEditor in resources/js/resina/editors.js).
    Each zone: a recipe or a free color (the recipe is then computed from
    each user's paints), an optional reference color, and its tab.
--}}
<x-resina.payload id="resina-zone-editor" :data="$payload" />

<div x-data="resinaZoneEditor('resina-zone-editor')" class="space-y-3">
    <template x-for="(row, index) in rows" :key="row.uid">
        <x-card class="space-y-3">
            <input type="hidden" :name="`zones[${index}][id]`" :value="row.id">

            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold dark:bg-white/10" x-text="index + 1"></span>
                <input type="text" :name="`zones[${index}][name]`" x-model="row.name" required aria-label="Nome zona"
                       class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm font-medium dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                <button type="button" @click="move(index, -1)" :disabled="index === 0" title="Sposta su"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
                    <x-heroicon-o-arrow-up class="h-4 w-4" />
                </button>
                <button type="button" @click="move(index, 1)" :disabled="index === rows.length - 1" title="Sposta giù"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
                    <x-heroicon-o-arrow-down class="h-4 w-4" />
                </button>
                <button type="button" @click="remove(index)" title="Elimina zona"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                    <x-heroicon-o-trash class="h-4 w-4" />
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="text-xs font-medium text-gray-500">Ricetta</label>
                    <select :name="`zones[${index}][recipe_id]`" x-model="row.recipe_id" @change="recipeChanged(row)"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <option value="">— colore libero —</option>
                        <template x-if="inlineFor(row)">
                            <option :value="row.recipe_id" selected x-text="inlineFor(row).title"></option>
                        </template>
                        <template x-for="category in categories" :key="category.name">
                            <optgroup :label="category.name">
                                <template x-for="recipe in category.recipes" :key="recipe.id">
                                    <option :value="String(recipe.id)" x-text="recipe.title" :selected="String(recipe.id) === row.recipe_id"></option>
                                </template>
                            </optgroup>
                        </template>
                    </select>
                    <a x-show="inlineFor(row)" :href="inlineFor(row)?.editUrl" class="text-xs font-medium text-gray-500 underline">Modifica i passaggi propri</a>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-gray-500">Scheda</label>
                    <select :name="`zones[${index}][tab]`" x-model="row.tab"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <option value="">Automatica (dal nome)</option>
                        <template x-for="(label, key) in tabs" :key="key">
                            <option :value="key" x-text="label" :selected="key === row.tab"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" x-model="row.useTarget" :disabled="! row.recipe_id"
                           class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                    <span x-text="row.recipe_id ? 'Colore di riferimento' : 'Colore libero'"></span>
                </label>
                <input type="color" :name="`zones[${index}][target_hex]`" x-model="row.target_hex" :disabled="! row.useTarget"
                       aria-label="Colore" class="h-9 w-14 cursor-pointer rounded-lg border border-gray-200 bg-gray-50 p-1 disabled:opacity-30 dark:border-white/10 dark:bg-white/5">
                <span class="text-xs text-gray-400" x-show="! row.recipe_id">La ricetta si calcola dai colori di ogni utente.</span>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-medium text-gray-500">Nota (facoltativa)</label>
                <input type="text" :name="`zones[${index}][note]`" x-model="row.note"
                       class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
            </div>
        </x-card>
    </template>

    <p x-show="! rows.length" class="text-sm text-gray-500">Nessuna zona.</p>

    <button type="button" @click="add()"
            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
        <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi zona
    </button>
</div>
