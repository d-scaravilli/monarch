<x-app-layout>
    <x-slot name="header">Utenti</x-slot>

    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
            <x-heroicon-o-plus class="h-4 w-4" />
            Nuovo utente
        </a>
    </div>

    <livewire:users-table />
</x-app-layout>
