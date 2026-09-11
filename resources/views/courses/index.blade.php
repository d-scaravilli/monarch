<x-app-layout>
    <x-slot name="header">Edizioni</x-slot>

    @if ($editions->isEmpty())
        <x-card class="text-center text-gray-500 py-10">
            Nessuna edizione disponibile.
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($editions as $edition)
                <a href="{{ route('editions.show', $edition) }}">
                    <x-card class="h-full hover:ring-gray-300 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $edition->course->name }}</p>
                                <p class="text-sm text-gray-500">{{ $edition->room->name }} &middot; {{ $edition->year }}</p>
                            </div>
                            <x-heroicon-o-chevron-right class="h-5 w-5 text-gray-300 shrink-0" />
                        </div>

                        <div class="mt-4 flex items-center gap-2 text-sm text-gray-600">
                            <x-heroicon-o-users class="h-4 w-4" />
                            {{ $edition->enrollments_count }} {{ $edition->enrollments_count === 1 ? 'iscritto' : 'iscritti' }}
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
