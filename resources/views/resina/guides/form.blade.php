@php $editing = $guide->exists; @endphp

<x-app-layout>
    <x-slot name="header">{{ $editing ? 'Modifica '.$guide->title : 'Nuova guida' }}</x-slot>

    <x-resina.payload id="resina-guide-editor" :data="$payload" />

    <div class="max-w-3xl space-y-4">
        <a href="{{ route('resina.projects.show', [$project, 'passo']) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> {{ $project->name }} · Passo passo
        </a>

        <x-resina.form-errors />

        <form method="POST" action="{{ $editing ? route('resina.guides.update', [$project, $guide]) : route('resina.guides.store', $project) }}"
              x-data="resinaGuideEditor('resina-guide-editor')" class="space-y-4">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <x-card class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-[1fr_7rem]">
                    <div class="space-y-1.5">
                        <x-input-label for="title" value="Titolo" />
                        <x-text-input id="title" name="title" value="{{ old('title', $guide->title) }}" class="w-full" required />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="position" value="Posizione" />
                        <x-text-input id="position" name="position" type="number" min="1" value="{{ old('position', $guide->position) }}" class="w-full" required />
                    </div>
                </div>
                <div class="space-y-1.5">
                    <x-input-label for="intro" value="Introduzione" />
                    <textarea id="intro" name="intro" rows="2" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('intro', $guide->intro) }}</textarea>
                </div>
            </x-card>

            <div>
                <x-section-header>Passaggi, in ordine</x-section-header>
                <div class="space-y-3">
                    <template x-for="(step, index) in steps" :key="step.uid">
                        <x-card class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold dark:bg-white/10" x-text="index + 1"></span>
                                <input type="text" :name="`steps[${index}][title]`" x-model="step.title" required placeholder="Titolo del passaggio"
                                       class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm font-medium dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                <span x-show="step.paints.length" class="h-9 w-9 shrink-0 rounded-xl ring-1 ring-black/10" :style="swatch(step)"></span>
                                <button type="button" @click="moveStep(index, -1)" :disabled="index === 0" title="Sposta su"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
                                    <x-heroicon-o-arrow-up class="h-4 w-4" />
                                </button>
                                <button type="button" @click="moveStep(index, 1)" :disabled="index === steps.length - 1" title="Sposta giù"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/5">
                                    <x-heroicon-o-arrow-down class="h-4 w-4" />
                                </button>
                                <button type="button" @click="removeStep(index)" x-show="steps.length > 1" title="Elimina passaggio"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </div>
                            <textarea :name="`steps[${index}][description]`" x-model="step.description" rows="2" placeholder="Cosa fare"
                                      class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100"></textarea>
                            <div class="space-y-2">
                                <template x-for="(paint, p) in step.paints" :key="p">
                                    <div class="flex items-center gap-2">
                                        <input type="number" min="1" max="40" :name="`steps[${index}][paints][${p}][drops]`" x-model.number="paint.drops" aria-label="Gocce"
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
                                        <button type="button" @click="removePaint(step, p)" title="Togli colore" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-400 hover:text-red-600">
                                            <x-heroicon-o-x-mark class="h-4 w-4" />
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="addPaint(step)" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300">+ Colore in gocce (facoltativo)</button>
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
                <x-primary-button>{{ $editing ? 'Salva guida' : 'Crea guida' }}</x-primary-button>
                <a href="{{ route('resina.projects.show', [$project, 'passo']) }}" class="text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400">Annulla</a>
            </div>
        </form>

        @if ($editing)
            <x-resina.delete-card :action="route('resina.guides.destroy', [$project, $guide])" title="Elimina guida" confirm="Eliminare {{ $guide->title }}?">
                Le armature che la usavano perdono solo il collegamento.
            </x-resina.delete-card>
        @endif
    </div>
</x-app-layout>
