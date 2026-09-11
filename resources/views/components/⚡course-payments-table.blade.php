<?php

use App\Models\Payment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $courseId;

    #[Url]
    public string $search = '';

    public string $sortField = 'date';

    public string $sortDirection = 'desc';

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

    public function deletePayment(int $paymentId): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        Payment::findOrFail($paymentId)->delete();
    }

    public function with(): array
    {
        $payments = Payment::query()
            ->with('enrollment.user')
            ->whereHas('enrollment', fn ($q) => $q->where('course_id', $this->courseId))
            ->when($this->search, function ($q) {
                $q->whereHas('enrollment.user', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%"));
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return ['payments' => $payments];
    }
}
?>

<div class="space-y-3">
    <div class="relative">
        <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca per iscritto..."
               class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
    </div>

    <x-card class="p-0 overflow-hidden" wire:loading.class="opacity-60">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 text-left">Iscritto</th>
                    <th class="px-5 py-3 text-left">
                        <button wire:click="sortBy('date')" class="hover:text-gray-600 dark:hover:text-gray-200">Data</button>
                    </th>
                    <th class="px-5 py-3 text-left hidden sm:table-cell">Metodo</th>
                    <th class="px-5 py-3 text-right">
                        <button wire:click="sortBy('amount')" class="hover:text-gray-600 dark:hover:text-gray-200">Importo</button>
                    </th>
                    <th class="px-5 py-3 text-right">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @forelse ($payments as $payment)
                    <tr wire:key="payment-{{ $payment->id }}">
                        <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-gray-100">{{ $payment->enrollment->user->name }}</td>
                        <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400">{{ $payment->date->translatedFormat('d M Y') }}</td>
                        <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ $payment->method }}</td>
                        <td class="px-5 py-3.5 text-right font-semibold text-gray-900 dark:text-gray-100">€{{ number_format($payment->amount, 2) }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <button type="button" wire:click="deletePayment({{ $payment->id }})" wire:confirm="Eliminare questo pagamento?" class="text-gray-400 hover:text-red-600">
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">Nessun pagamento registrato.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    @if ($payments->hasPages())
        <div>{{ $payments->links() }}</div>
    @endif
</div>
