<?php

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    /** @var array<int, array{phone: ?string, fiscal_code: ?string, owns_sword: bool, shirt_given: bool, has_borrowed_equipment: bool, borrowed_equipment_notes: ?string}> */
    public array $rows = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Every cell saves on its own the moment it changes — there's no
     * "save all" step. Livewire's generic updated() hook is the only way
     * to react to a dynamic "rows.{userId}.{field}" property path.
     */
    public function updated(string $name): void
    {
        if (! str_starts_with($name, 'rows.')) {
            return;
        }

        [, $userId, $field] = explode('.', $name);
        $this->save((int) $userId, $field);
    }

    private function save(int $userId, string $field): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $allowed = ['phone', 'fiscal_code', 'owns_sword', 'shirt_given', 'has_borrowed_equipment', 'borrowed_equipment_notes'];
        abort_unless(in_array($field, $allowed, true), 403);

        $value = $this->rows[$userId][$field] ?? null;

        $rules = match ($field) {
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\s().-]+$/'],
            'fiscal_code' => ['nullable', 'string', 'max:32'],
            'borrowed_equipment_notes' => ['nullable', 'string', 'max:255'],
            default => ['boolean'],
        };

        Validator::make([$field => $value], [$field => $rules])->validate();

        $updates = [$field => $value];

        // Clearing the checkbox drops the description with it — there's
        // nothing left to describe once nothing is on loan.
        if ($field === 'has_borrowed_equipment' && ! $value) {
            $updates['borrowed_equipment_notes'] = null;
            $this->rows[$userId]['borrowed_equipment_notes'] = null;
        }

        MemberProfile::updateOrCreate(['user_id' => $userId], $updates);

        $this->dispatch('field-saved', userId: $userId, field: $field);
    }

    public function with(): array
    {
        $users = User::role('member')
            ->with('memberProfile')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(20);

        foreach ($users as $user) {
            $this->rows[$user->id] = [
                'phone' => $user->memberProfile?->phone,
                'fiscal_code' => $user->memberProfile?->fiscal_code,
                'owns_sword' => (bool) $user->memberProfile?->owns_sword,
                'shirt_given' => (bool) $user->memberProfile?->shirt_given,
                'has_borrowed_equipment' => (bool) $user->memberProfile?->has_borrowed_equipment,
                'borrowed_equipment_notes' => $user->memberProfile?->borrowed_equipment_notes,
            ];
        }

        return ['users' => $users];
    }
}
?>

<div class="space-y-3">
    <div class="relative">
        <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca per nome..."
               class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
    </div>

    <x-card class="p-0 overflow-hidden" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Iscritto</th>
                        <th class="px-5 py-3 text-left">Telefono</th>
                        <th class="px-5 py-3 text-left">Codice fiscale</th>
                        <th class="px-5 py-3 text-center">Spada propria</th>
                        <th class="px-5 py-3 text-center">Maglietta consegnata</th>
                        <th class="px-5 py-3 text-left">Attrezzatura in prestito</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($users as $user)
                        <tr wire:key="member-{{ $user->id }}"
                            x-data="{ saved: null }"
                            x-on:field-saved.window="if ($event.detail.userId === {{ $user->id }}) { saved = $event.detail.field; setTimeout(() => { if (saved === $event.detail.field) saved = null }, 1200) }"
                        >
                            <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $user->name }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <input type="text" wire:model.blur="rows.{{ $user->id }}.phone"
                                           class="w-40 rounded-lg border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
                                    <x-heroicon-o-check-circle class="h-4 w-4 text-green-600 shrink-0" x-show="saved === 'phone'" x-cloak />
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <input type="text" wire:model.blur="rows.{{ $user->id }}.fiscal_code"
                                           class="w-36 rounded-lg border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
                                    <x-heroicon-o-check-circle class="h-4 w-4 text-green-600 shrink-0" x-show="saved === 'fiscal_code'" x-cloak />
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <input type="checkbox" wire:model.live="rows.{{ $user->id }}.owns_sword"
                                           class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900" />
                                    <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" x-show="saved === 'owns_sword'" x-cloak />
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <input type="checkbox" wire:model.live="rows.{{ $user->id }}.shirt_given"
                                           class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900" />
                                    <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" x-show="saved === 'shirt_given'" x-cloak />
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" wire:model.live="rows.{{ $user->id }}.has_borrowed_equipment"
                                               class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900" />
                                        <span class="text-xs text-gray-500">In prestito</span>
                                        <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" x-show="saved === 'has_borrowed_equipment'" x-cloak />
                                    </div>
                                    @if ($rows[$user->id]['has_borrowed_equipment'] ?? false)
                                        <div class="flex items-center gap-2">
                                            <input type="text" wire:model.blur="rows.{{ $user->id }}.borrowed_equipment_notes"
                                                   placeholder="Cosa..."
                                                   class="w-48 rounded-lg border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
                                            <x-heroicon-o-check-circle class="h-4 w-4 text-green-600 shrink-0" x-show="saved === 'borrowed_equipment_notes'" x-cloak />
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">Nessun iscritto trovato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($users->hasPages())
        <div>{{ $users->links(data: ['scrollTo' => false]) }}</div>
    @endif
</div>
