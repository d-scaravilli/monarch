<x-app-layout>
    <x-slot name="header">Iscritti</x-slot>

    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex-1 flex gap-2">
            <div class="relative flex-1">
                <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cerca per nome o email..."
                       class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
            </div>
            <select name="status" onchange="this.form.submit()" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                <option value="">Tutti</option>
                <option value="active" @selected(request('status') === 'active')>Attivi</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inattivi</option>
            </select>
            <button type="submit" class="hidden sm:block rounded-xl border border-gray-200 dark:border-white/10 px-3 text-sm text-gray-500">Filtra</button>
        </form>

        <a href="{{ route('members.create') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white shrink-0">
            <x-heroicon-o-plus class="h-4 w-4" />
            Nuovo iscritto
        </a>
    </div>

    <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
        @forelse ($members as $member)
            <x-swipe-row :action="route('members.destroy', $member)" delete-label="Elimina">
                <a href="{{ route('members.show', $member) }}" class="flex items-center justify-between px-5 py-3.5">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $member->name }}</p>
                        <p class="text-xs text-gray-400">{{ $member->email }}</p>
                    </div>
                    <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-300" />
                </a>
            </x-swipe-row>
        @empty
            <p class="px-5 py-6 text-sm text-gray-500">Nessun iscritto trovato.</p>
        @endforelse
    </x-card>

    <div class="mt-4">
        {{ $members->links() }}
    </div>
</x-app-layout>
