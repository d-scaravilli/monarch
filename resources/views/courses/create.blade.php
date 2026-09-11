<x-app-layout>
    <x-slot name="header">Nuovo corso</x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('courses.store') }}" class="space-y-5">
            @php $course = new App\Models\Course; @endphp
            @include('courses._form')

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Crea corso</x-primary-button>
                <a href="{{ route('courses.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
