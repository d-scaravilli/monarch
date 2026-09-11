<x-section-header>Elimina account</x-section-header>
<x-card>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Una volta eliminato, l'account e i dati collegati non saranno più accessibili. Scarica prima ciò che vuoi conservare.
    </p>

    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        Elimina account
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('settings.account.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Sei sicuro di voler eliminare il tuo account?
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Questa azione è irreversibile. Inserisci la password per confermare.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Password" class="sr-only" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-3/4" placeholder="Password" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                <x-danger-button>Elimina account</x-danger-button>
            </div>
        </form>
    </x-modal>
</x-card>
