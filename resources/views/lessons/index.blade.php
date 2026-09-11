<x-app-layout>
    <x-slot name="header">Lezioni</x-slot>

    @if (auth()->user()->hasRole('admin'))
        <div class="flex justify-end gap-2 mb-4">
            <a href="{{ route('lessons.generate') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <x-heroicon-o-square-3-stack-3d class="h-4 w-4" />
                Genera lezioni
            </a>
            <a href="{{ route('lessons.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
                <x-heroicon-o-plus class="h-4 w-4" />
                Nuova lezione
            </a>
        </div>
    @endif

    <livewire:lessons-table :course-id="$selectedCourseId" />
</x-app-layout>
