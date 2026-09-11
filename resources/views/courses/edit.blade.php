<x-app-layout>
    <x-slot name="header">Modifica corso</x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('courses.update', $course) }}" class="space-y-6">
            @method('PUT')
            @include('courses._form')

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Salva modifiche</x-primary-button>
                <a href="{{ route('courses.show', $course) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
