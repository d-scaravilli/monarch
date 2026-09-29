@php
    $editing = $recipe->exists;
    $inline = $recipe->is_inline;
@endphp

<x-app-layout>
    <x-slot name="header">{{ $editing ? 'Modifica ricetta' : 'Nuova ricetta' }}</x-slot>

    <x-resina.payload id="resina-recipe-editor" :data="$payload" />

    <div class="max-w-3xl space-y-4">
        <a href="{{ $backUrl }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> {{ $inline ? 'Personaggio' : 'Ricettario' }}
        </a>

        @if ($inline)
            <p class="text-sm text-gray-500 dark:text-gray-400">Questi sono i passaggi propri di una sola zona: non compaiono nel Ricettario.</p>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                <p class="font-medium">Controlla la ricetta:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach (collect($errors->all())->unique() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $editing ? route('resina.recipes.update', $recipe) : route('resina.recipes.store') }}"
              x-data="resinaRecipeEditor('resina-recipe-editor')" class="space-y-4">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <x-card class="space-y-4">
                <div class="space-y-1.5">
                    <x-input-label for="title" value="Titolo" />
                    <x-text-input id="title" name="title" value="{{ old('title', $recipe->title) }}" class="w-full" required />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5" @if ($inline) hidden @endif>
                        <x-input-label for="recipe_category_id" value="Categoria" />
                        <select id="recipe_category_id" name="recipe_category_id" @required(! $inline) @disabled($inline)
                                class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('recipe_category_id', $recipe->recipe_category_id) === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="who" value="Per chi (facoltativo)" />
                        <x-text-input id="who" name="who" value="{{ old('who', $recipe->who) }}" class="w-full" placeholder="Es. Seiya, Shiryu · eroi con pelle calda" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="tip" value="Consiglio (facoltativo)" />
                    <textarea id="tip" name="tip" rows="2"
                              class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('tip', $recipe->tip) }}</textarea>
                </div>

                <div>
                    <p class="mb-1 text-[11px] text-gray-400">Toni, dal più scuro al più chiaro</p>
                    <div class="inline-flex overflow-hidden rounded-lg ring-1 ring-black/10 dark:ring-white/10">
                        <template x-for="(tone, t) in scale" :key="t">
                            <span class="h-6 w-6" :style="tone.style" :title="tone.title"></span>
                        </template>
                    </div>
                </div>
            </x-card>

            <div>
                <x-section-header>Passaggi, nell'ordine in cui si dipingono</x-section-header>
                <p class="mb-3 text-xs text-gray-400">
                    Facoltativo, tecnica e superficie si compilano da soli quando scrivi il ruolo: puoi comunque cambiarli.
                </p>

                <div class="space-y-3">
                    <template x-for="(step, index) in steps" :key="step.uid">
                        <x-card class="space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold dark:bg-white/10" x-text="index + 1"></span>
                                <span class="h-9 w-9 shrink-0 rounded-xl ring-1 ring-black/10 dark:ring-white/10" :style="swatch(step)"></span>
                                <code class="text-xs text-gray-500" x-text="hex(step)"></code>
                                <div class="ml-auto flex items-center gap-1">
                                    <button type="button" @click="moveStep(index, -1)" :disabled="index === 0" title="Sposta su"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
                                        <x-heroicon-o-arrow-up class="h-4 w-4" />
                                    </button>
                                    <button type="button" @click="moveStep(index, 1)" :disabled="index === steps.length - 1" title="Sposta giù"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
                                        <x-heroicon-o-arrow-down class="h-4 w-4" />
                                    </button>
                                    <button type="button" @click="removeStep(index)" x-show="steps.length > 1" title="Elimina passaggio"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500">Ruolo</label>
                                    <input type="text" :name="`steps[${index}][role]`" x-model="step.role" @change="roleChanged(step)" required
                                           placeholder="Base, Ombra, Luce, Spigoli…"
                                           class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500">Uso</label>
                                    <input type="text" :name="`steps[${index}][usage]`" x-model="step.usage" @change="usageChanged(step)"
                                           placeholder="Dove e come si stende"
                                           class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500">Tecnica</label>
                                    <select :name="`steps[${index}][technique]`" x-model="step.technique"
                                            class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                        <option value="">—</option>
                                        <template x-for="t in techniques" :key="t.code">
                                            <option :value="t.code" x-text="t.label" :selected="t.code === step.technique"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-500">Superficie %</label>
                                    <input type="number" min="1" max="100" :name="`steps[${index}][coverage]`" x-model="step.coverage"
                                           class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                </div>
                                <label class="col-span-2 flex items-center gap-2 self-end pb-2.5 text-sm text-gray-700 sm:col-span-1 dark:text-gray-300">
                                    <input type="hidden" :name="`steps[${index}][optional]`" value="0">
                                    <input type="checkbox" :name="`steps[${index}][optional]`" value="1" x-model="step.optional"
                                           class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                                    Facoltativo
                                </label>
                            </div>

                            <div class="space-y-2">
                                <p class="text-xs font-medium text-gray-500">Colori in gocce</p>
                                <template x-for="(paint, p) in step.paints" :key="p">
                                    <div class="flex items-center gap-2">
                                        <input type="number" min="1" max="40" :name="`steps[${index}][paints][${p}][drops]`" x-model.number="paint.drops"
                                               aria-label="Gocce"
                                               class="w-16 shrink-0 rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                        <select :name="`steps[${index}][paints][${p}][paint_id]`" x-model="paint.paint_id" required aria-label="Colore"
                                                class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                            <option value="">Scegli un colore</option>
                                            <template x-for="group in paintGroups" :key="group.line">
                                                <optgroup :label="group.line">
                                                    <template x-for="option in group.paints" :key="option.id">
                                                        <option :value="String(option.id)" x-text="option.label" :selected="String(option.id) === paint.paint_id"></option>
                                                    </template>
                                                </optgroup>
                                            </template>
                                        </select>
                                        <button type="button" @click="removePaint(step, p)" x-show="step.paints.length > 1" title="Togli colore"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-400 hover:text-red-600">
                                            <x-heroicon-o-x-mark class="h-4 w-4" />
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="addPaint(step)" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300">
                                    + Aggiungi colore
                                </button>
                            </div>
                        </x-card>
                    </template>
                </div>

                <button type="button" @click="addStep()"
                        class="mt-3 inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                    <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi passaggio
                </button>
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>{{ $editing ? 'Salva ricetta' : 'Crea ricetta' }}</x-primary-button>
                <a href="{{ $backUrl }}" class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400">Annulla</a>
            </div>
        </form>

        @if ($editing && ! $inline)
            <x-card class="ring-1 ring-red-200 dark:ring-red-500/30 bg-red-50/50 dark:bg-red-500/5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">Elimina ricetta</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if ($usedBy)
                                Non si può eliminare: è usata da {{ implode(', ', $usedBy) }}.
                            @else
                                Non è usata da nessun personaggio, armatura o progetto.
                            @endif
                        </p>
                    </div>
                    @unless ($usedBy)
                        <form method="POST" action="{{ route('resina.recipes.destroy', $recipe) }}"
                              onsubmit="return confirm('Eliminare questa ricetta? L\'azione non può essere annullata.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                                <x-heroicon-o-trash class="h-4 w-4" /> Elimina
                            </button>
                        </form>
                    @endunless
                </div>
            </x-card>
        @endif
    </div>
</x-app-layout>
