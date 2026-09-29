<x-app-layout>
    <x-slot name="header">I miei colori</x-slot>

    <div class="space-y-6">
        <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            I flaconi che possiedi, con il codice Vallejo e a cosa servono. I valori esadecimali sono indicativi. Quando compri
            un colore nuovo, aggiungilo qui: entra subito nel mixer e nella ricerca delle ricette.
        </p>

        <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
            Il set Tanned Skin 72.380 contiene una base, un'ombra e due luci. Qui sono usati così: <b>ombra</b> Pelle Barbaro
            72.071, <b>base</b> Pelle Elfi 72.004, <b>luci</b> Pelle 72.099 e Carne Elfica 72.098. Se il foglietto della
            confezione indica altri ruoli, segui il foglietto.
        </div>

        <livewire:resina-paints-table />

        <div>
            <x-section-header>Aggiungi un colore</x-section-header>
            <x-card>
                <form method="POST" action="{{ route('resina.paints.store') }}" class="grid gap-3 sm:grid-cols-[1fr_9rem_auto_9rem_auto] sm:items-end">
                    @csrf
                    <div class="space-y-1">
                        <x-input-label for="paint-name" value="Nome" />
                        <x-text-input id="paint-name" name="name" value="{{ old('name') }}" placeholder="Es. Royal Purple" class="w-full" required />
                    </div>
                    <div class="space-y-1">
                        <x-input-label for="paint-code" value="Codice" />
                        <x-text-input id="paint-code" name="code" value="{{ old('code') }}" placeholder="Es. 72.016" class="w-full" />
                    </div>
                    <div class="space-y-1">
                        <x-input-label for="paint-hex" value="Colore" />
                        <input id="paint-hex" type="color" name="hex" value="{{ old('hex', '#5b3a86') }}"
                               class="h-[42px] w-full cursor-pointer rounded-xl border border-gray-200 bg-gray-50 p-1 sm:w-14 dark:border-white/10 dark:bg-white/5">
                    </div>
                    <div class="space-y-1">
                        <x-input-label for="paint-type" value="Tipo" />
                        <select id="paint-type" name="type" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="normal" @selected(old('type') === 'normal')>Normale</option>
                            <option value="metallic" @selected(old('type') === 'metallic')>Metallico</option>
                            <option value="wash" @selected(old('type') === 'wash')>Wash</option>
                            <option value="airbrush" @selected(old('type') === 'airbrush')>Aerografo</option>
                        </select>
                    </div>
                    <x-primary-button class="justify-center">Aggiungi</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                <x-input-error :messages="$errors->get('hex')" class="mt-2" />
                <p class="mt-3 text-xs text-gray-400">
                    Per l'hex, scegli il colore più simile al flacone o copia quello della scheda del produttore. Oppure usa
                    «L'ho comprato» in <a href="{{ route('resina.shop.index') }}" class="underline">Da comprare</a>.
                </p>
            </x-card>
        </div>

        <a href="{{ route('resina.shop.index') }}" class="block">
            <x-card class="flex items-center justify-between gap-3 transition hover:ring-gray-300 dark:hover:ring-white/20">
                <span class="flex items-center gap-3">
                    <x-heroicon-o-shopping-bag class="h-5 w-5 text-gray-400" />
                    <span>
                        <span class="block font-medium text-gray-900 dark:text-gray-100">Colori consigliati da comprare</span>
                        <span class="block text-sm text-gray-500 dark:text-gray-400">Con la miscela più vicina che puoi già fare e «L'ho comprato».</span>
                    </span>
                </span>
                <x-heroicon-o-chevron-right class="h-5 w-5 text-gray-300" />
            </x-card>
        </a>
    </div>
</x-app-layout>
