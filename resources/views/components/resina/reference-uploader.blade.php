@props(['mode' => 'panel', 'version' => null, 'searchUrl' => null])

{{--
    Reference photo loader (resinaReferenceUploader, resources/js/resina/reference.js).
    mode "panel": a sheet opened by the event `resina-reference-open` with a version;
    it attaches the photo straight away. mode "form": inline in the new-version
    form, it fills the hidden reference_token / reference_source fields.
--}}
@php
    $options = ['temporaryUrl' => route('resina.references.temporary'), 'mode' => $mode, 'version' => $version];
    $accent = \App\Support\ModuleTheme::classes($currentModule?->color);
@endphp

<div x-data="resinaReferenceUploader({{ Illuminate\Support\Js::from($options) }})"
     @if ($mode === 'panel') @resina-reference-open.window="show($event.detail)" @keydown.escape.window="close()" @paste.window="open && pasted($event)" @endif>

    @if ($mode === 'form')
        <input type="hidden" name="reference_token" :value="token">
        {{-- Only with a new photo: otherwise the form's own Fonte field (if any) stays. --}}
        <template x-if="token">
            <input type="hidden" name="reference_source" :value="source">
        </template>
    @endif

    <div @if ($mode === 'panel') x-show="open" x-cloak class="fixed inset-0 z-40 flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" @endif>
        @if ($mode === 'panel')
            <div class="absolute inset-0 bg-black/50" @click="close()"></div>
        @endif

        <div class="{{ $mode === 'panel' ? 'relative max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-xl sm:max-w-lg sm:rounded-3xl dark:bg-gray-900' : '' }} space-y-4">
            @if ($mode === 'panel')
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Foto di riferimento</p>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100" x-text="version?.title"></h3>
                    </div>
                    <button type="button" @click="close()" aria-label="Chiudi" class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>
            @endif

            {{-- Preview of the loaded photo. --}}
            <template x-if="status === 'preview' || status === 'saving'">
                <div class="space-y-3">
                    <img :src="preview" alt="Anteprima" class="mx-auto max-h-64 rounded-2xl object-contain ring-1 ring-black/10 dark:ring-white/10">
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-gray-500">Fonte (da dove viene la foto)</label>
                        <input type="text" x-model="source" maxlength="255" placeholder="Es. sito, libro, foto mia"
                               class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($mode === 'panel')
                            <button type="button" @click="confirm()" :disabled="status === 'saving'"
                                    class="flex-1 rounded-xl px-4 py-3 text-sm font-semibold text-white disabled:opacity-50 {{ $accent['badge'] }}"
                                    x-text="status === 'saving' ? 'Salvo…' : 'Usa questa foto'"></button>
                        @else
                            <p class="flex items-center gap-1.5 text-sm text-green-700 dark:text-green-400"><x-heroicon-o-check-circle class="h-5 w-5" /> Foto pronta: verrà salvata con la versione.</p>
                        @endif
                        <button type="button" @click="reset()" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">Cambia</button>
                    </div>
                </div>
            </template>

            <template x-if="status === 'idle' || status === 'working'">
                <div class="space-y-3">
                    {{-- Paste / drop zone: editable so that iOS offers «Incolla», but it never keeps text. --}}
                    <div x-ref="pasteZone" contenteditable="true" inputmode="none" spellcheck="false" tabindex="0"
                         @if ($mode === 'form') @paste="pasted($event)" @endif @beforeinput.prevent @drop.prevent="dropped($event)"
                         @dragover.prevent="dragging = true" @dragenter.prevent="dragging = true" @dragleave.prevent="dragging = false"
                         class="flex min-h-36 cursor-text flex-col items-center justify-center gap-1 rounded-2xl border-2 border-dashed px-4 py-6 text-center text-sm caret-transparent outline-none transition focus:border-gray-900 dark:focus:border-white"
                         :class="dragging ? 'border-gray-900 bg-gray-50 dark:border-white dark:bg-white/5' : 'border-gray-200 text-gray-500 dark:border-white/15 dark:text-gray-400'">
                        <template x-if="status === 'working'">
                            <span class="flex items-center gap-2"><x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" /> Preparo la foto…</span>
                        </template>
                        <template x-if="status === 'idle'">
                            <span contenteditable="false" class="pointer-events-none select-none">
                                <x-heroicon-o-clipboard-document class="mx-auto mb-1 h-7 w-7 text-gray-400" />
                                <b class="block text-gray-700 dark:text-gray-200">Incolla qui l'immagine</b>
                                Cmd+V sul Mac · tocca e scegli «Incolla» su iPhone · oppure trascinala
                            </span>
                        </template>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                            <x-heroicon-o-photo class="h-4 w-4" /> Scegli file
                            <input type="file" accept="image/*" class="hidden" @change="fromFile($event.target.files[0]); $event.target.value = ''">
                        </label>
                        <button type="button" x-show="canReadClipboard" @click="pasteFromClipboard()"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                            <x-heroicon-o-clipboard class="h-4 w-4" /> Incolla
                        </button>
                        <a :href="version?.searchUrl ?? {{ Illuminate\Support\Js::from($searchUrl) }}" x-show="version?.searchUrl || {{ Illuminate\Support\Js::from((bool) $searchUrl) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4" /> Cerca su Google Immagini
                        </a>
                    </div>

                    <form class="flex gap-2" @submit.prevent="fromAddress(address)">
                        <input type="url" x-model="address" placeholder="…oppure incolla l'indirizzo dell'immagine" inputmode="url"
                               class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <button type="submit" :disabled="! address || status === 'working'"
                                class="rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">Scarica</button>
                    </form>
                </div>
            </template>

            <p x-show="error" x-text="error" class="rounded-xl bg-red-50 px-3.5 py-2.5 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400"></p>
        </div>
    </div>
</div>
