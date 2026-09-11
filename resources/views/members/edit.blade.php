<x-app-layout>
    <x-slot name="header">Modifica anagrafica</x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('members.update', $member) }}" class="space-y-5">
            @method('PUT')
            @include('members._form')

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Salva modifiche</x-primary-button>
                <a href="{{ route('members.show', $member) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
