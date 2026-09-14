@php
    $initialTab = 'general';
    if ($errors->has('confirm_name')) {
        $initialTab = 'danger';
    } elseif ($errors->has('user_id')) {
        $initialTab = 'access';
    } elseif ($errors->any()) {
        $initialTab = 'general';
    }
@endphp

<x-app-layout>
    <x-slot name="header">Gestisci</x-slot>

    <div class="max-w-2xl space-y-6" x-data="{ tab: '{{ $initialTab }}' }">
        <div class="inline-flex flex-wrap gap-1 rounded-xl bg-gray-100 dark:bg-white/5 p-1 text-sm font-medium">
            <button type="button" @click="tab = 'general'" class="rounded-lg px-4 py-2 transition" :class="tab === 'general' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Generale</button>
            <button type="button" @click="tab = 'access'" class="rounded-lg px-4 py-2 transition" :class="tab === 'access' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Accessi</button>
            @if ($module->slug === 'palestra')
                <button type="button" @click="tab = 'rooms'" class="rounded-lg px-4 py-2 transition" :class="tab === 'rooms' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">Sale</button>
                <button type="button" @click="tab = 'danger'" class="rounded-lg px-4 py-2 transition" :class="tab === 'danger' ? 'bg-white dark:bg-gray-900 shadow-sm text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">Zona pericolosa</button>
            @endif
        </div>

        <div x-show="tab === 'general'" x-cloak>
            <x-card>
                <form method="POST" action="{{ route('modules.settings.update', $module) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center gap-4">
                        <x-module-badge :icon="$module->icon" :color="$module->color" :image="$module->imageUrl()" size="h-14 w-14" />
                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <x-heroicon-o-photo class="h-4 w-4" />
                            Carica immagine
                            <input type="file" name="image" accept="image/*" class="hidden">
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('image')" class="mt-1" />

                    <div class="space-y-1.5">
                        <x-input-label for="name" value="Nome" />
                        <x-text-input id="name" name="name" value="{{ old('name', $module->name) }}" class="w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label for="description" value="Descrizione (mostrata nel launcher)" />
                        <x-text-input id="description" name="description" value="{{ old('description', $module->description) }}" class="w-full" />
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label value="Icona" />
                        <div class="grid grid-cols-8 gap-2">
                            @foreach ($iconChoices as $icon)
                                <label class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-gray-200 dark:border-white/10 has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                                    <input type="radio" name="icon" value="{{ $icon }}" class="hidden" @checked(old('icon', $module->icon) === $icon)>
                                    <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5 text-gray-600 dark:text-gray-300" />
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label value="Colore d'accento" />
                        <div class="flex flex-wrap gap-2">
                            @foreach ($colorChoices as $color)
                                @php $c = \App\Support\ModuleTheme::classes($color); @endphp
                                <label class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full ring-2 ring-transparent has-[:checked]:ring-gray-900 dark:has-[:checked]:ring-white">
                                    <input type="radio" name="color" value="{{ $color }}" class="hidden" @checked(old('color', $module->color) === $color)>
                                    <span class="h-7 w-7 rounded-full {{ $c['badge'] }}"></span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if ($module->slug === 'palestra')
                        <div class="space-y-1.5" x-data="{ coverStyle: {{ Illuminate\Support\Js::from(old('member_cover_style', $module->member_cover_style)) }} }">
                            <x-input-label value="Copertina scheda iscritto" />
                            <p class="text-xs text-gray-400">Lo sfondo del banner sopra l'avatar in ogni scheda iscritto: il colore d'accento, un'immagine, oppure nessuno sfondo.</p>

                            <div class="flex flex-wrap gap-2">
                                <label class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-1.5 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                                    <input type="radio" name="member_cover_style" value="color" x-model="coverStyle" class="hidden">
                                    Colore
                                </label>
                                <label class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-1.5 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                                    <input type="radio" name="member_cover_style" value="image" x-model="coverStyle" class="hidden">
                                    Immagine
                                </label>
                                <label class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-1.5 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                                    <input type="radio" name="member_cover_style" value="transparent" x-model="coverStyle" class="hidden">
                                    Trasparente
                                </label>
                            </div>

                            <div x-show="coverStyle === 'image'" x-cloak class="flex items-center gap-4 pt-1">
                                <span class="flex h-14 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl {{ \App\Support\ModuleTheme::classes($module->color)['soft'] }} dark:bg-white/5">
                                    @if ($module->memberCoverImageUrl())
                                        <img src="{{ $module->memberCoverImageUrl() }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <span class="text-xs {{ \App\Support\ModuleTheme::classes($module->color)['text'] }}">Nessuna</span>
                                    @endif
                                </span>
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                    <x-heroicon-o-photo class="h-4 w-4" />
                                    Carica immagine
                                    <input type="file" name="member_cover_image" accept="image/*" class="hidden">
                                </label>
                                @if ($module->memberCoverImageUrl())
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                        <input type="checkbox" name="remove_member_cover_image" value="1" class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                                        Elimina immagine
                                    </label>
                                @endif
                            </div>
                            <x-input-error :messages="$errors->get('member_cover_image')" class="mt-1" />
                        </div>
                    @endif

                    <label class="flex items-center justify-between rounded-xl bg-gray-50 dark:bg-white/5 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Modulo attivo</span>
                        <input type="checkbox" name="is_active" value="1" class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900" @checked(old('is_active', $module->is_active))>
                    </label>

                    <x-primary-button>Salva</x-primary-button>
                </form>
            </x-card>
        </div>

        <div x-show="tab === 'access'" x-cloak>
            <p class="text-xs text-gray-400 mb-2">Gli admin vedono sempre tutto, indipendentemente da questo elenco.</p>

            <livewire:module-access-table :module-id="$module->id" />

            <x-card class="mt-3">
                <form method="POST" action="{{ route('modules.settings.access.store', $module) }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1 space-y-1.5">
                        <x-input-label value="Concedi accesso a" />
                        <select name="user_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            @forelse ($availableUsers as $availableUser)
                                <option value="{{ $availableUser->id }}">{{ $availableUser->name }} ({{ $availableUser->email }})</option>
                            @empty
                                <option value="">Tutti gli utenti hanno già accesso</option>
                            @endforelse
                        </select>
                    </div>
                    <x-primary-button>Aggiungi</x-primary-button>
                </form>
            </x-card>
        </div>

        @if ($module->slug === 'palestra')
            <div x-show="tab === 'rooms'" x-cloak>
                <a href="{{ route('rooms.index') }}">
                    <x-card class="flex items-center justify-between hover:ring-gray-300 dark:hover:ring-white/20 transition">
                        <span class="flex items-center gap-3">
                            <x-heroicon-o-building-office-2 class="h-5 w-5 text-gray-400" />
                            <span class="font-medium text-gray-900 dark:text-gray-100">Gestisci sale</span>
                        </span>
                        <x-heroicon-o-chevron-right class="h-5 w-5 text-gray-300" />
                    </x-card>
                </a>
            </div>

            <div x-show="tab === 'danger'" x-cloak>
                <x-card class="ring-1 ring-red-200 dark:ring-red-500/30 bg-red-50/50 dark:bg-red-500/5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">Azzera dati modulo</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Cancella iscrizioni, corsi/eventi, fasce orarie, lezioni, presenze, pagamenti,
                                documenti e note. Account utente, sale e impostazioni del modulo restano intatti.
                                Azione irreversibile.
                            </p>
                        </div>
                        <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'reset-module-data')"
                                class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                            <x-heroicon-o-exclamation-triangle class="h-4 w-4" /> Azzera dati modulo
                        </button>
                    </div>
                </x-card>
            </div>

            <x-modal name="reset-module-data" max-width="lg">
                <div class="p-6" x-data="{ confirmText: '' }">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-500" />
                        Azzera dati modulo
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Stai per cancellare in modo permanente iscrizioni, corsi/eventi, fasce orarie, lezioni,
                        presenze, pagamenti, documenti e note del modulo <strong>{{ $module->name }}</strong>.
                        Account utente, sale e impostazioni del modulo non verranno toccati. Questa azione non
                        può essere annullata.
                    </p>

                    <form method="POST" action="{{ route('modules.settings.reset', $module) }}" class="mt-5 space-y-4">
                        @csrf @method('DELETE')
                        <div class="space-y-1.5">
                            <x-input-label value="Digita \"{{ $module->name }}\" per confermare" />
                            <x-text-input name="confirm_name" x-model="confirmText" class="w-full" autocomplete="off" />
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                            <button type="submit"
                                    :disabled="confirmText !== {{ Illuminate\Support\Js::from($module->name) }}"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                Azzera definitivamente
                            </button>
                        </div>
                    </form>
                </div>
            </x-modal>
        @endif
    </div>
</x-app-layout>
