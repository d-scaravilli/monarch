<x-app-layout>
    <x-slot name="header">Presenze &middot; {{ $lesson->date->translatedFormat('d M Y') }}</x-slot>

    <form method="POST" action="{{ route('editions.attendance.update', [$courseEdition, $lesson]) }}" class="space-y-6">
        @csrf

        <x-card class="p-0 divide-y divide-gray-100">
            @forelse ($courseEdition->enrollments as $enrollment)
                <label class="flex items-center justify-between px-5 py-4 cursor-pointer">
                    <div>
                        <p class="font-medium text-gray-900">{{ $enrollment->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $enrollment->user->email }}</p>
                    </div>
                    <input type="checkbox"
                           name="present[{{ $enrollment->id }}]"
                           value="1"
                           @checked($attendances->get($enrollment->id, false))
                           class="h-5 w-5 rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                </label>
            @empty
                <p class="px-5 py-6 text-sm text-gray-500">Nessun iscritto per questa edizione.</p>
            @endforelse
        </x-card>

        <div class="flex items-center gap-3">
            <x-primary-button>Salva presenze</x-primary-button>
            <a href="{{ route('editions.show', $courseEdition) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
        </div>
    </form>
</x-app-layout>
