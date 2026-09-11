<?php

use App\Models\Module;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $moduleId;

    #[Url]
    public string $search = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function revoke(int $userId): void
    {
        $module = Module::findOrFail($this->moduleId);
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $module->users()->detach($userId);
    }

    public function with(): array
    {
        $module = Module::findOrFail($this->moduleId);

        $users = $module->users()
            ->when($this->search, function ($q) {
                $q->where(fn ($q2) => $q2->where('users.name', 'like', "%{$this->search}%")->orWhere('users.email', 'like', "%{$this->search}%"));
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return ['accessUsers' => $users];
    }
}
?>

<div class="space-y-3">
    <div class="relative">
        <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca per nome o email..."
               class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
    </div>

    <x-card class="p-0 overflow-hidden" wire:loading.class="opacity-60">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 text-left">
                        <button wire:click="sortBy('name')" class="hover:text-gray-600 dark:hover:text-gray-200">Nome</button>
                    </th>
                    <th class="px-5 py-3 text-left hidden sm:table-cell">Email</th>
                    <th class="px-5 py-3 text-right">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @forelse ($accessUsers as $accessUser)
                    <tr wire:key="access-{{ $accessUser->id }}">
                        <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-gray-100">{{ $accessUser->name }}</td>
                        <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ $accessUser->email }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <button type="button" wire:click="revoke({{ $accessUser->id }})" wire:confirm="Rimuovere l'accesso a {{ $accessUser->name }}?" class="text-gray-400 hover:text-red-600">
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-6 text-center text-sm text-gray-500">Nessun utente con accesso diretto.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    @if ($accessUsers->hasPages())
        <div>{{ $accessUsers->links() }}</div>
    @endif
</div>
