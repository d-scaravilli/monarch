<x-app-layout>
    <x-slot name="header">Modifica utente</x-slot>

    <div class="max-w-xl space-y-6">
        <x-card>
            <form method="POST" action="{{ route('admin.users.update', $editUser) }}" class="space-y-5">
                @method('PUT')
                @include('admin.users._form')

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button>Salva modifiche</x-primary-button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
                </div>
            </form>
        </x-card>

        <div>
            <x-section-header>Imposta nuova password</x-section-header>
            <x-card>
                <form method="POST" action="{{ route('admin.users.password', $editUser) }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <div class="space-y-1.5">
                        <x-input-label for="password" value="Nuova password" />
                        <x-text-input id="password" type="password" name="password" class="w-full" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="password_confirmation" value="Conferma password" />
                        <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="w-full" />
                    </div>
                    <x-primary-button>Imposta password</x-primary-button>
                </form>
            </x-card>
        </div>

        @unless ($editUser->is(auth()->user()))
            <div>
                <x-section-header>Zona pericolosa</x-section-header>
                <x-card class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $editUser->isDisabled() ? 'Account disabilitato' : 'Disabilita accesso' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $editUser->isDisabled() ? 'Non può accedere finché non lo riabiliti.' : 'Blocca il login e chiude le sessioni attive.' }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route($editUser->isDisabled() ? 'admin.users.enable' : 'admin.users.disable', $editUser) }}">
                            @csrf
                            @if ($editUser->isDisabled())
                                <x-secondary-button type="submit">Riabilita</x-secondary-button>
                            @else
                                <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl font-semibold text-sm text-amber-700 bg-amber-50 dark:bg-amber-500/10 dark:text-amber-400 hover:bg-amber-100">
                                    Disabilita
                                </button>
                            @endif
                        </form>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-100 dark:border-white/10 pt-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Elimina utente</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Soft delete: lo storico collegato resta consultabile.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.users.destroy', $editUser) }}" onsubmit="return confirm('Eliminare questo utente?')">
                            @csrf @method('DELETE')
                            <x-danger-button>Elimina</x-danger-button>
                        </form>
                    </div>
                </x-card>
            </div>
        @endunless
    </div>
</x-app-layout>
