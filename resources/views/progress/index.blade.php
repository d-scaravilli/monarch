<x-app-layout>
    <x-slot name="header">Progressi</x-slot>

    @if ($members->isEmpty())
        <x-card class="text-center text-gray-500 py-10">
            Nessuna nota registrata.
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($members as $member)
                <x-member-progress-card :member="$member" />
            @endforeach
        </div>
    @endif
</x-app-layout>
