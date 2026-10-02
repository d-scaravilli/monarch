@php
    $field = 'w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100';
    $lines = fn (?array $items) => implode("\n", $items ?? []);
@endphp

<x-app-layout>
    <x-slot name="header">Istruzioni delle tecniche</x-slot>

    <div class="max-w-3xl space-y-4">
        <a href="{{ route('resina.techniques.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> Tecniche
        </a>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            Sono i testi che la modalità pittura mostra in ogni passaggio: cosa preparare, come si fa, quanto aspettare, come deve
            venire, errori da evitare. Nei campi su più righe, <b>una riga è una voce</b> dell'elenco.
        </p>

        <x-resina.form-errors />

        <form method="POST" action="{{ route('resina.technique-guides.update') }}" class="space-y-4">
            @csrf @method('PUT')

            @foreach ($guides as $guide)
                <details class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10" @if ($loop->first) open @endif>
                    <summary class="flex cursor-pointer items-center justify-between gap-2 px-5 py-4">
                        <span><b class="text-gray-900 dark:text-gray-100">{{ $guide->name }}</b> <code class="ml-1 text-xs text-gray-400">{{ $guide->code }}</code></span>
                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-400" />
                    </summary>
                    <div class="grid gap-3 border-t border-gray-100 p-5 dark:border-white/10">
                        <div class="grid gap-3 sm:grid-cols-[1fr_9rem]">
                            <label class="space-y-1">
                                <span class="text-xs font-medium text-gray-500">Nome</span>
                                <input type="text" name="guides[{{ $guide->code }}][name]" value="{{ old("guides.{$guide->code}.name", $guide->name) }}" required class="{{ $field }}">
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-medium text-gray-500">Quanto aspettare (minuti)</span>
                                <input type="number" min="0" max="1440" name="guides[{{ $guide->code }}][wait_minutes]" value="{{ old("guides.{$guide->code}.wait_minutes", $guide->wait_minutes) }}" class="{{ $field }}">
                            </label>
                        </div>
                        <label class="space-y-1">
                            <span class="text-xs font-medium text-gray-500">Preparazione (una voce per riga)</span>
                            <textarea name="guides[{{ $guide->code }}][preparation]" rows="3" class="{{ $field }}">{{ old("guides.{$guide->code}.preparation", $lines($guide->preparation)) }}</textarea>
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-medium text-gray-500">Come si fa (una voce per riga, in ordine)</span>
                            <textarea name="guides[{{ $guide->code }}][steps]" rows="5" class="{{ $field }}">{{ old("guides.{$guide->code}.steps", $lines($guide->steps)) }}</textarea>
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-medium text-gray-500">Come deve venire</span>
                            <textarea name="guides[{{ $guide->code }}][result]" rows="2" class="{{ $field }}">{{ old("guides.{$guide->code}.result", $guide->result) }}</textarea>
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-medium text-gray-500">Errori da evitare (una voce per riga)</span>
                            <textarea name="guides[{{ $guide->code }}][mistakes]" rows="3" class="{{ $field }}">{{ old("guides.{$guide->code}.mistakes", $lines($guide->mistakes)) }}</textarea>
                        </label>
                    </div>
                </details>
            @endforeach

            <details class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10">
                <summary class="flex cursor-pointer items-center justify-between gap-2 px-5 py-4">
                    <b class="text-gray-900 dark:text-gray-100">Varianti e sessione</b>
                    <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-400" />
                </summary>
                <div class="grid gap-3 border-t border-gray-100 p-5 dark:border-white/10">
                    @foreach ($textLabels as $key => $label)
                        <label class="space-y-1">
                            <span class="text-xs font-medium text-gray-500">{{ $label }}</span>
                            <textarea name="texts[{{ $key }}]" rows="{{ count($texts[$key]?->lines ?? []) > 1 ? 4 : 2 }}" class="{{ $field }}">{{ old("texts.{$key}", $lines($texts[$key]?->lines)) }}</textarea>
                        </label>
                    @endforeach
                </div>
            </details>

            <details class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10">
                <summary class="flex cursor-pointer items-center justify-between gap-2 px-5 py-4">
                    <b class="text-gray-900 dark:text-gray-100">Titoli semplici dei passaggi</b>
                    <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-400" />
                </summary>
                <div class="space-y-2 border-t border-gray-100 p-5 dark:border-white/10">
                    <p class="text-xs text-gray-400">A sinistra i ruoli a cui si applica (non modificabile), a destra il titolo mostrato.</p>
                    @foreach ($titles as $title)
                        <div class="grid items-center gap-2 sm:grid-cols-[10rem_1fr]">
                            <code class="truncate text-xs text-gray-500" title="{{ $title->pattern }}">{{ $title->pattern }}</code>
                            <input type="text" name="titles[{{ $title->id }}]" value="{{ old("titles.{$title->id}", $title->title) }}" required maxlength="255" class="{{ $field }}">
                        </div>
                    @endforeach
                </div>
            </details>

            <x-primary-button>Salva le istruzioni</x-primary-button>
        </form>
    </div>
</x-app-layout>
