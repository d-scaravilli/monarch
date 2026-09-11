<x-app-layout>
    <x-slot name="header">Nuovo utente</x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @include('admin.users._form')

            <p class="text-xs text-gray-400">La password iniziale ti sarà mostrata dopo il salvataggio.</p>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Crea utente</x-primary-button>
                <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
