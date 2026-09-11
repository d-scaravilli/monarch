<x-app-layout>
    <x-slot name="header">Nuovo iscritto</x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('members.store') }}" class="space-y-5">
            @php $member = new App\Models\User; @endphp
            @include('members._form')

            <p class="text-xs text-gray-400">Verrà creato anche un account utente con ruolo "iscritto"; la password iniziale ti sarà mostrata dopo il salvataggio.</p>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Crea iscritto</x-primary-button>
                <a href="{{ route('members.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
