<?php

use App\Notifications\NewMessageNotification;
use App\Notifications\NoteAddedNotification;
use App\Notifications\PaymentRegisteredNotification;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    /**
     * @var array<string, string>
     */
    private const TYPE_LABELS = [
        NewMessageNotification::class => 'Nuovo messaggio',
        PaymentRegisteredNotification::class => 'Pagamento registrato',
        NoteAddedNotification::class => 'Nuova nota',
    ];

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

    public function deleteNotification(string $notificationId): void
    {
        DatabaseNotification::whereKey($notificationId)->delete();
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? class_basename($type);
    }

    public function with(): array
    {
        $notifications = DatabaseNotification::query()
            ->with('notifiable')
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->whereHas('notifiable', fn ($q3) => $q3->where('name', 'like', "%{$this->search}%"))
                        ->orWhere('data', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return ['notifications' => $notifications];
    }
}
?>

<div class="space-y-3">
    <div class="relative">
        <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca per destinatario o contenuto..."
               class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
    </div>

    <x-card class="p-0 overflow-hidden" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-white/10 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Destinatario</th>
                        <th class="px-5 py-3 text-left">Tipo</th>
                        <th class="px-5 py-3 text-left">Contenuto</th>
                        <th class="px-5 py-3 text-left">
                            <button wire:click="sortBy('created_at')" class="hover:text-gray-600 dark:hover:text-gray-200">Data</button>
                        </th>
                        <th class="px-5 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($notifications as $notification)
                        <tr wire:key="notification-{{ $notification->id }}">
                            <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $notification->notifiable?->name ?? 'Utente eliminato' }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <x-badge>{{ static::typeLabel($notification->type) }}</x-badge>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 max-w-sm">
                                <p class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $notification->data['title'] ?? '—' }}</p>
                                @if (! empty($notification->data['body']))
                                    <p class="truncate">{{ $notification->data['body'] }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $notification->created_at->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <button type="button" wire:click="deleteNotification('{{ $notification->id }}')" wire:confirm="Eliminare questa notifica?" class="text-gray-400 hover:text-red-600">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">Nessuna notifica trovata.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($notifications->hasPages())
        <div>{{ $notifications->links(data: ['scrollTo' => false]) }}</div>
    @endif
</div>
