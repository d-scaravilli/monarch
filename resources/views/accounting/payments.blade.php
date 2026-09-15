<x-app-layout>
    <x-slot name="header">Storico pagamenti</x-slot>

    <div class="space-y-4">
        <a href="{{ route('accounting.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700">
            <x-heroicon-o-chevron-left class="h-4 w-4" /> Torna a Contabilità
        </a>

        <livewire:all-payments-table />
    </div>
</x-app-layout>
