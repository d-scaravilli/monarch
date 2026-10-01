@php $accent = \App\Support\ModuleTheme::classes($currentModule?->color); @endphp

<x-app-layout>
    <x-slot name="header">Percorso principiante</x-slot>

    <x-resina.payload id="resina-path" :data="$payload" />

    <div x-data="resinaPath('resina-path')" class="max-w-3xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Dieci passi per arrivare alla prima figura senza bruciare la statua buona. Spunta ogni passo quando l'hai fatto:
                il progresso resta salvato nel tuo account.
            </p>
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('resina.path.edit') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                    <x-heroicon-o-pencil-square class="h-4 w-4" /> Modifica i passi
                </a>
            @endif
        </div>

        <p x-show="error" x-cloak x-text="error" class="rounded-xl bg-red-50 px-4 py-2.5 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400"></p>

        <x-card class="space-y-4">
            <div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    <div class="h-full rounded-full transition-all {{ $accent['badge'] }}" :style="'width:' + pct + '%'"></div>
                </div>
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400"><span x-text="doneCount"></span> di <span x-text="total"></span> passi</p>
            </div>

            <ol class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($steps as $step)
                    @php $link = \App\Http\Controllers\Resina\PathController::linkFor($step); @endphp
                    <li class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                              :class="done[{{ $step->id }}] ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300'">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100" :class="done[{{ $step->id }}] && 'line-through decoration-gray-400'">{{ $step->title }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $step->description }}</p>
                            @if ($link)
                                <a href="{{ $link }}" class="text-xs font-medium {{ $accent['text'] }}">Come si fa</a>
                            @endif
                        </div>
                        <input type="checkbox" aria-label="Fatto" :checked="!! done[{{ $step->id }}]" @change="toggle({{ $step->id }}, $event.target.checked)"
                               class="mt-1 h-5 w-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                    </li>
                @endforeach
            </ol>
        </x-card>

        <div>
            <x-section-header>Ordine per dipingere una figura</x-section-header>
            <x-card class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Si dipinge dall'interno verso l'esterno: prima quello che sta sotto, poi quello che sta sopra. Se sbordi, lo
                    strato dopo copre l'errore.
                </p>
                <ol class="space-y-3">
                    <template x-for="(step, i) in order" :key="i">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="i + 1"></span>
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="step.title"></span>
                                    <template x-if="step.brush">
                                        <a :href="step.brush.href" :title="step.brush.title" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300">🖌 <span x-text="step.brush.label"></span></a>
                                    </template>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="step.description"></p>
                                <x-resina.ingredients list="step.ingredients" class="mt-1" />
                            </div>
                        </li>
                    </template>
                </ol>
            </x-card>
        </div>
    </div>
</x-app-layout>
