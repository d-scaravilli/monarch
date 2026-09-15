@props(['payment'])

<x-modal :name="'edit-payment-'.$payment->id" max-width="lg">
    <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Modifica pagamento</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->enrollment->user->name }} &middot; {{ $payment->enrollment->course->discipline->name }}</p>

        <form method="POST" action="{{ route('payments.update', $payment) }}" class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="space-y-1.5">
                    <x-input-label value="Importo €" />
                    <x-text-input type="number" step="0.01" min="0" name="amount" value="{{ $payment->amount }}" class="w-full" required />
                </div>
                <div class="space-y-1.5">
                    <x-input-label value="Metodo" />
                    <select name="method" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <option value="contanti" @selected($payment->method === 'contanti')>Contanti</option>
                        <option value="bonifico" @selected($payment->method === 'bonifico')>Bonifico</option>
                        <option value="carta" @selected($payment->method === 'carta')>Carta</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <x-input-label value="Data" />
                    <x-text-input type="date" name="date" value="{{ $payment->date->toDateString() }}" class="w-full" />
                </div>
            </div>

            <div class="space-y-1.5">
                <x-input-label value="Note" />
                <textarea name="notes" rows="2" placeholder="Facoltative..."
                          class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ $payment->notes }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                <x-primary-button>Salva</x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
