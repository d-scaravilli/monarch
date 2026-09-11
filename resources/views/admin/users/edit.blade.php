<x-app-layout>
    <x-slot name="header">Modifica utente</x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('admin.users.update', $editUser) }}" class="space-y-5">
            @method('PUT')
            @include('admin.users._form')

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Salva modifiche</x-primary-button>
                <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
