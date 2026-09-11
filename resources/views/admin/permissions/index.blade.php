<x-app-layout>
    <x-slot name="header">Permessi</x-slot>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        I ruoli sono l'unico livello di permesso usato oggi dall'app. Attiva o disattiva un ruolo per utente: il cambiamento è salvato subito.
    </p>

    <div class="mb-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-400 ring-1 ring-amber-100 dark:ring-amber-500/20 flex items-start gap-2">
        <x-heroicon-o-exclamation-triangle class="h-4 w-4 shrink-0 mt-0.5" />
        <p>Il controllo di accesso per singola pagina (oltre al ruolo) sarà sviluppato in una fase successiva. Per ora l'accesso è determinato solo dal ruolo.</p>
    </div>

    <x-card class="mb-4">
        <x-section-header>Cosa abilita ogni ruolo</x-section-header>
        <div class="space-y-3">
            @foreach ($roles as $role)
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize">{{ $role }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $roleDescriptions[$role] }}</p>
                </div>
            @endforeach
        </div>
    </x-card>

    <x-card class="p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Utente</th>
                        @foreach ($roles as $role)
                            <th class="px-5 py-3 text-center capitalize">{{ $role }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($users as $tableUser)
                        <tr>
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $tableUser->name }}</p>
                                <p class="text-xs text-gray-400">{{ $tableUser->email }}</p>
                            </td>
                            @foreach ($roles as $role)
                                <td class="px-5 py-3.5 text-center">
                                    <form method="POST" action="{{ route('admin.permissions.update') }}">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $tableUser->id }}">
                                        <input type="hidden" name="role" value="{{ $role }}">
                                        <input type="hidden" name="enabled" value="{{ $tableUser->hasRole($role) ? '0' : '1' }}">
                                        <button type="submit"
                                                class="h-5 w-5 rounded-md border {{ $tableUser->hasRole($role) ? 'bg-gray-900 dark:bg-white border-gray-900 dark:border-white' : 'border-gray-300 dark:border-white/20' }}">
                                            @if ($tableUser->hasRole($role))
                                                <x-heroicon-o-check-circle class="h-5 w-5 text-white dark:text-gray-900 -m-px" />
                                            @endif
                                        </button>
                                    </form>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>
