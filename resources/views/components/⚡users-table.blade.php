<?php

use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    /** @var array<int, int> */
    public array $selected = [];

    public function updated($property): void
    {
        if (in_array($property, ['search', 'role', 'status'])) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    /**
     * @param  array<int, int>  $ids  the current page's user ids, known
     *                                to the view from $users already
     */
    public function toggleSelectAllOnPage(array $ids): void
    {
        $allSelected = $ids !== [] && count(array_diff($ids, $this->selected)) === 0;

        $this->selected = $allSelected
            ? array_values(array_diff($this->selected, $ids))
            : array_values(array_unique([...$this->selected, ...$ids]));
    }

    /**
     * Same soft-delete as the single-user action (Admin\UserController)
     * — just applied to every selected user at once. The current admin's
     * own row never gets a checkbox in the view, but a Livewire component
     * method is still directly callable, so the exclusion is repeated
     * here rather than trusted to the UI alone.
     */
    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $ids = array_diff($this->selected, [auth()->id()]);
        User::whereIn('id', $ids)->get()->each->delete();

        $this->selected = [];
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

    public function with(): array
    {
        $users = User::query()
            ->with('roles', 'modules')
            ->when($this->search, function ($q) {
                $q->where(fn ($q2) => $q2->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"));
            })
            ->when($this->role, fn ($q) => $q->role($this->role))
            ->when($this->status === 'enabled', fn ($q) => $q->whereNull('disabled_at'))
            ->when($this->status === 'disabled', fn ($q) => $q->whereNotNull('disabled_at'))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return ['users' => $users];
    }
}
?>

<div class="space-y-4">
    <x-card>
        <div class="flex flex-wrap items-end gap-3">
            <div class="relative flex-1 min-w-[10rem]">
                <x-input-label value="Cerca" class="mb-1.5" />
                <x-heroicon-o-magnifying-glass class="absolute left-3 top-[2.35rem] h-4 w-4 text-gray-400" />
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nome o email..."
                       class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
            </div>
            <div>
                <x-input-label value="Ruolo" class="mb-1.5" />
                <select wire:model.live="role" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">Tutti</option>
                    <option value="admin">Admin</option>
                    <option value="instructor">Instructor</option>
                    <option value="member">Member</option>
                </select>
            </div>
            <div>
                <x-input-label value="Stato" class="mb-1.5" />
                <select wire:model.live="status" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">Tutti</option>
                    <option value="enabled">Abilitato</option>
                    <option value="disabled">Disabilitato</option>
                </select>
            </div>
        </div>
    </x-card>

    @if ($selected !== [])
        <x-card class="!py-3 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ count($selected) }} selezionati</p>
            <button type="button" wire:click="bulkDelete"
                    wire:confirm="Eliminare {{ count($selected) }} utenti? Soft delete: lo storico collegato resta consultabile."
                    class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                <x-heroicon-o-trash class="h-4 w-4" /> Elimina selezionati
            </button>
        </x-card>
    @endif

    <x-card class="p-0 overflow-hidden" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 w-10">
                            @php $pageIds = $users->pluck('id')->diff([auth()->id()])->values()->all(); @endphp
                            <input type="checkbox"
                                   wire:click="toggleSelectAllOnPage({{ Illuminate\Support\Js::from($pageIds) }})"
                                   @checked($pageIds !== [] && count(array_diff($pageIds, $selected)) === 0)
                                   class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                        </th>
                        <th class="px-5 py-3 text-left">
                            <button wire:click="sortBy('name')" class="hover:text-gray-600 dark:hover:text-gray-200">Nome</button>
                        </th>
                        <th class="px-5 py-3 text-left hidden sm:table-cell">Email</th>
                        <th class="px-5 py-3 text-left">Ruoli</th>
                        <th class="px-5 py-3 text-left hidden lg:table-cell">
                            <button wire:click="sortBy('last_login_at')" class="hover:text-gray-600 dark:hover:text-gray-200">Ultimo accesso</button>
                        </th>
                        <th class="px-5 py-3 text-left hidden lg:table-cell">
                            <button wire:click="sortBy('created_at')" class="hover:text-gray-600 dark:hover:text-gray-200">Creato il</button>
                        </th>
                        <th class="px-5 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($users as $tableUser)
                        <tr wire:key="user-{{ $tableUser->id }}">
                            <td class="px-5 py-3.5">
                                @unless ($tableUser->is(auth()->user()))
                                    <input type="checkbox" wire:model.live="selected" value="{{ $tableUser->id }}"
                                           class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                                @endunless
                            </td>
                            <td class="px-5 py-3.5 text-gray-900 dark:text-gray-100 font-medium">
                                {{ $tableUser->name }}
                                @if ($tableUser->isDisabled())
                                    <x-badge color="red" class="ml-1.5">disabilitato</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ $tableUser->email }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($tableUser->roles as $userRole)
                                        <x-badge>{{ $userRole->name }}</x-badge>
                                    @empty
                                        <span class="text-gray-300">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                                {{ $tableUser->last_login_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                                {{ $tableUser->created_at->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.users.edit', $tableUser) }}" class="text-gray-500 hover:text-gray-900 dark:hover:text-white">
                                    <x-heroicon-o-pencil class="h-4 w-4 inline" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">Nessun utente trovato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($users->hasPages())
        <div>{{ $users->links() }}</div>
    @endif
</div>
