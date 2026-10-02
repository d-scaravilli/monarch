@php $accent = \App\Support\ModuleTheme::classes($currentModule?->color); @endphp

<x-app-layout>
    <x-slot name="header">Foto di riferimento</x-slot>

    <x-resina.payload id="resina-references" :data="$payload" />

    <div x-data="resinaReferenceChecklist('resina-references')" class="space-y-5">
        <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Ogni versione dei personaggi ha la sua foto: è quella che si guarda mentre si dipinge. Le vedono tutti gli utenti del
            modulo. Le scegli e le carichi tu: incollando l'immagine, il suo indirizzo, trascinandola o scegliendo un file.
        </p>

        <x-card class="space-y-3">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    <b class="text-lg" x-text="missing.length"></b> foto mancanti su <span x-text="versions.length"></span>
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" x-model="onlyMissing" class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                        Solo mancanti
                    </label>
                    <button type="button" @click="copyMissing()" :disabled="! missing.length"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                        <x-heroicon-o-clipboard-document class="h-4 w-4" />
                        <span x-text="copied ? 'Copiato ✓' : 'Copia elenco mancanti'"></span>
                    </button>
                </div>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                <div class="h-full rounded-full {{ $accent['badge'] }}" :style="'width:' + (versions.length ? Math.round((versions.length - missing.length) / versions.length * 100) : 0) + '%'"></div>
            </div>
        </x-card>

        <template x-for="project in projects" :key="project.name">
            <section class="space-y-3" x-show="project.characters.some((c) => characterVisible(c))">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="project.name"></h2>
                <template x-for="group in project.groups" :key="group.slug ?? 'none'">
                    <div x-show="charactersOf(project, group.slug).length">
                        <x-section-header><span x-text="group.name"></span></x-section-header>
                        <div class="space-y-3">
                            <template x-for="character in charactersOf(project, group.slug)" :key="character.href">
                                <x-card class="space-y-3">
                                    <a :href="character.href" class="font-semibold text-gray-900 hover:underline dark:text-gray-100" x-text="character.name"></a>
                                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                        <template x-for="version in character.versions.filter((v) => visible(v))" :key="version.id">
                                            <div class="flex gap-3 rounded-2xl bg-gray-50 p-3 dark:bg-white/5">
                                                <button type="button" @click="$dispatch('resina-reference-open', version)" class="shrink-0" :title="version.photo ? 'Sostituisci' : 'Carica'">
                                                    <template x-if="version.photo">
                                                        <img :src="version.photo.thumb" alt="" loading="lazy" class="h-20 w-20 rounded-xl object-cover ring-1 ring-black/10 dark:ring-white/10">
                                                    </template>
                                                    <template x-if="! version.photo">
                                                        <span class="flex h-20 w-20 items-center justify-center rounded-xl border-2 border-dashed border-gray-300 text-xs text-gray-400 dark:border-white/15">Manca</span>
                                                    </template>
                                                </button>
                                                <div class="min-w-0 flex-1 space-y-1.5">
                                                    <p class="text-sm">
                                                        <b class="text-gray-900 dark:text-gray-100" x-text="version.label"></b>
                                                        <span class="text-gray-500 dark:text-gray-400" x-text="version.subtitle"></span>
                                                    </p>
                                                    <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-medium"
                                                          :class="version.photo ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'"
                                                          x-text="version.photo ? 'Presente' : 'Manca'"></span>
                                                    <input type="text" x-model="version.source" @change="saveSource(version)" placeholder="Fonte" maxlength="255" aria-label="Fonte"
                                                           class="w-full rounded-lg border-gray-200 bg-white px-2 py-1 text-xs dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs font-medium">
                                                        <button type="button" @click="$dispatch('resina-reference-open', version)" class="{{ $accent['text'] }}" x-text="version.photo ? 'Sostituisci' : 'Carica'"></button>
                                                        <a :href="version.searchUrl" target="_blank" rel="noopener" class="text-gray-500 hover:text-gray-800 dark:text-gray-400">Cerca su Google Immagini</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </x-card>
                            </template>
                        </div>
                    </div>
                </template>
            </section>
        </template>

        <p x-show="onlyMissing && ! missing.length" x-cloak class="py-6 text-center text-sm text-gray-500">Non manca nessuna foto. ✓</p>
    </div>

    <x-resina.reference-uploader mode="panel" />
</x-app-layout>
