@props(['action', 'title', 'confirm'])

<x-card class="ring-1 ring-red-200 dark:ring-red-500/30 bg-red-50/50 dark:bg-red-500/5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $title }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $slot }}</p>
        </div>
        <form method="POST" action="{{ $action }}" onsubmit="return confirm({{ Illuminate\Support\Js::from($confirm) }})">
            @csrf @method('DELETE')
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                <x-heroicon-o-trash class="h-4 w-4" /> Elimina
            </button>
        </form>
    </div>
</x-card>
