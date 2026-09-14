@props(['name', 'enrollments'])

<x-modal :name="$name" max-width="lg">
    <div class="p-6" x-data="{ url: '' }">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Registra pagamento</h2>

        <form method="POST" :action="url" class="mt-5 space-y-4">
            @csrf
            <div class="space-y-1.5">
                <x-input-label value="Iscritto / Corso" />
                <select @change="url = $event.target.value" required
                        class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">Seleziona...</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ route('enrollments.payments.store', $enrollment) }}">
                            {{ $enrollment->user->name }} &middot; {{ $enrollment->course->discipline->name }} ({{ $enrollment->course->year }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="space-y-1.5">
                    <x-input-label value="Importo €" />
                    <x-text-input type="number" step="0.01" min="0" name="amount" class="w-full" required />
                </div>
                <div class="space-y-1.5">
                    <x-input-label value="Metodo" />
                    <select name="method" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <option value="contanti">Contanti</option>
                        <option value="bonifico">Bonifico</option>
                        <option value="carta">Carta</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <x-input-label value="Data" />
                    <x-text-input type="date" name="date" value="{{ now()->toDateString() }}" class="w-full" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                <x-primary-button>Registra</x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
