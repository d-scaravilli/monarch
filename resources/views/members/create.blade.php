<x-app-layout>
    <x-slot name="header">Nuovo iscritto</x-slot>

    <x-card class="max-w-xl" x-data="{ mode: 'new' }">
        <div class="flex gap-2 mb-6">
            <button type="button" @click="mode = 'new'"
                    :class="mode === 'new' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300'"
                    class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                Nuovo utente
            </button>
            <button type="button" @click="mode = 'link'"
                    :class="mode === 'link' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300'"
                    class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                Collega utente esistente
            </button>
        </div>

        <form method="POST" action="{{ route('members.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="mode" :value="mode">
            @php $member = new App\Models\User; @endphp

            <template x-if="mode === 'new'">
                <div class="space-y-5">
                    <div class="space-y-1.5">
                        <x-input-label for="name" value="Nome e cognome" />
                        <x-text-input id="name" name="name" value="{{ old('name') }}" class="w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" type="email" name="email" value="{{ old('email') }}" class="w-full" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                </div>
            </template>

            <template x-if="mode === 'link'">
                <div class="space-y-1.5">
                    <x-input-label value="Utente esistente" />
                    <select name="existing_user_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <option value="">Seleziona...</option>
                        @foreach ($linkableUsers as $user)
                            <option value="{{ $user->id }}" @selected(old('existing_user_id') == $user->id)>{{ $user->name }} ({{ $user->email }}){{ $user->hasRole('instructor') ? ' — istruttore' : '' }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('existing_user_id')" class="mt-1" />
                    <p class="text-xs text-gray-400">Utile per un istruttore (o un altro utente già presente) che si iscrive anche come allievo.</p>
                </div>
            </template>

            <div class="space-y-1.5">
                <x-input-label for="fiscal_code" value="Codice fiscale" />
                <x-text-input id="fiscal_code" name="fiscal_code" value="{{ old('fiscal_code') }}" class="w-full" />
                <x-input-error :messages="$errors->get('fiscal_code')" class="mt-1" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="phone" value="Contatto telefonico" />
                <x-text-input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="w-full" />
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="notes" value="Note" />
                <textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-1" />
            </div>

            <p class="text-xs text-gray-400" x-show="mode === 'new'" x-cloak>
                Verrà creato anche un account utente con ruolo "iscritto"; la password iniziale ti sarà mostrata dopo il salvataggio.
            </p>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Crea iscritto</x-primary-button>
                <a href="{{ route('members.team') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
