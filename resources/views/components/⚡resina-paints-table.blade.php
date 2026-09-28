<?php

use App\Models\Resina\UserPaint;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $type = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type'])) {
            $this->resetPage();
        }
    }

    /**
     * Only custom bottles can go: the starter paints are what every
     * recipe is built on.
     */
    public function remove(int $userPaintId): void
    {
        UserPaint::where('user_id', auth()->id())->whereNull('paint_id')->whereKey($userPaintId)->delete();
    }

    public function with(): array
    {
        $search = trim($this->search);

        $paints = UserPaint::query()
            ->where('resin_user_paints.user_id', auth()->id())
            ->leftJoin('resin_paints', 'resin_paints.id', '=', 'resin_user_paints.paint_id')
            ->select('resin_user_paints.*')
            ->with('paint')
            ->when($this->type !== '', fn ($q) => $q->whereRaw('COALESCE(resin_paints.type, resin_user_paints.type) = ?', [$this->type]))
            ->when($search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('resin_paints.name', 'like', "%{$search}%")
                ->orWhere('resin_paints.name_en', 'like', "%{$search}%")
                ->orWhere('resin_paints.code', 'like', "%{$search}%")
                ->orWhere('resin_paints.usage', 'like', "%{$search}%")
                ->orWhere('resin_user_paints.name', 'like', "%{$search}%")
                ->orWhere('resin_user_paints.code', 'like', "%{$search}%")))
            // Starter paints in catalog order, then the ones added by the user.
            ->orderByRaw('resin_user_paints.paint_id IS NULL')
            ->orderBy('resin_paints.position')
            ->orderBy('resin_user_paints.id')
            ->paginate(40);

        return ['userPaints' => $paints];
    }
};
?>

@php
    $typeLabels = ['normal' => 'Normali', 'metallic' => 'Metallici', 'wash' => 'Wash', 'airbrush' => 'Aerografo'];
@endphp

<div class="space-y-3">
    <div class="flex flex-col gap-2 sm:flex-row">
        <div class="relative flex-1">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca per nome, codice o uso..."
                   class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:border-gray-900 focus:bg-white focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
        </div>
        <select wire:model.live="type" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
            <option value="">Tutti i tipi</option>
            @foreach ($typeLabels as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <x-card class="overflow-hidden p-0" wire:loading.class="opacity-60">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:border-white/10">
                    <th class="w-12 px-4 py-3"></th>
                    <th class="px-2 py-3 text-left">Colore</th>
                    <th class="hidden px-4 py-3 text-left md:table-cell">Hex</th>
                    <th class="hidden px-4 py-3 text-left lg:table-cell">Linea</th>
                    <th class="hidden px-4 py-3 text-left sm:table-cell">A cosa serve</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @forelse ($userPaints as $userPaint)
                    @php
                        $paint = $userPaint->paint;
                        $type = $paint?->type ?? $userPaint->type;
                        $hex = $paint?->hex ?? $userPaint->hex;
                    @endphp
                    <tr wire:key="paint-{{ $userPaint->id }}">
                        <td class="px-4 py-3">
                            <x-resina.swatch :hex="$hex" :type="$type" size="h-9 w-9" wire:key="swatch-{{ $userPaint->id }}" />
                        </td>
                        <td class="px-2 py-3">
                            <x-resina.paint-name :name="$paint?->name ?? $userPaint->name" :code="$paint?->code ?? ($userPaint->code ?: '—')"
                                                 class="font-medium text-gray-900 dark:text-gray-100" />
                            @if ($paint?->name_en)
                                <span class="block text-xs text-gray-400">{{ $paint->name_en }}</span>
                            @endif
                            <span class="mt-0.5 flex flex-wrap gap-1">
                                @if ($type !== 'normal')
                                    <x-badge>{{ ['metallic' => 'metallico', 'wash' => 'wash', 'airbrush' => 'aerografo'][$type] ?? $type }}</x-badge>
                                @endif
                                @if ($userPaint->isCustom())
                                    <x-badge color="purple">aggiunto da te</x-badge>
                                @endif
                            </span>
                            <span class="mt-1 block text-xs text-gray-500 sm:hidden">{{ $paint?->usage ?? $userPaint->usage }}</span>
                        </td>
                        <td class="hidden px-4 py-3 md:table-cell"><code class="text-xs text-gray-500">{{ strtoupper($hex) }}</code></td>
                        <td class="hidden px-4 py-3 text-xs text-gray-500 lg:table-cell">{{ $paint?->line ?? 'Aggiunto da te' }}</td>
                        <td class="hidden px-4 py-3 text-xs text-gray-500 sm:table-cell">{{ $paint?->usage ?? $userPaint->usage }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($userPaint->isCustom())
                                <button type="button" wire:click="remove({{ $userPaint->id }})"
                                        wire:confirm="Togliere {{ $userPaint->name }} dai tuoi colori?"
                                        title="Rimuovi" class="text-gray-400 hover:text-red-600">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500">Nessun colore trovato.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    @if ($userPaints->hasPages())
        <div>{{ $userPaints->links() }}</div>
    @endif
</div>
