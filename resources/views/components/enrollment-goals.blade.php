@props(['enrollment', 'notes', 'canManage' => false])

@php
    $accent = \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray');
    $goals = $enrollment->goals;
    // Only this enrollment's course, and only the ones no goal has
    // claimed — notes linked to a goal are shown inside that goal's own
    // card instead, never duplicated here.
    $freeNotes = $notes
        ->filter(fn ($note) => is_null($note->goal_id) && $note->lesson && $note->lesson->course_id === $enrollment->course_id)
        ->values();
@endphp

<div>
    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $enrollment->course->discipline->name }} &middot; {{ $enrollment->course->year }}</p>
    <p class="text-xs text-gray-400 mb-4">Percorso e note</p>

    <div class="grid gap-4 lg:grid-cols-2 items-start">
        <div class="min-w-0">
            <div class="flex items-center justify-between mb-2">
                <x-section-header class="mb-0">Obiettivi</x-section-header>
                @if ($canManage)
                    <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'new-goal-{{ $enrollment->id }}')"
                            class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
                        <x-heroicon-o-plus class="h-3.5 w-3.5" /> Nuovo obiettivo
                    </button>
                @endif
            </div>

            <x-card>
                @if ($goals->isEmpty())
                    <p class="text-sm text-gray-500">Nessun obiettivo impostato per questo percorso.</p>
                @else
                    <div class="relative">
                        <div class="absolute left-[13px] top-3 bottom-3 w-px bg-gray-200 dark:bg-white/10"></div>

                        <div class="space-y-3">
                            @foreach ($goals as $goal)
                                <div class="relative pl-10" x-data="{ expanded: false }">
                                    <span class="absolute left-0 top-0 z-10 flex h-7 w-7 items-center justify-center rounded-full ring-4 ring-gray-50 dark:ring-gray-950 {{ $goal->isCompleted() ? 'bg-green-500' : $accent['badge'] }}">
                                        @if ($goal->isCompleted())
                                            <x-heroicon-o-check class="h-4 w-4 text-white" />
                                        @else
                                            <x-heroicon-o-flag class="h-3.5 w-3.5 text-white" />
                                        @endif
                                    </span>

                                    <button type="button" @click="expanded = !expanded" class="w-full text-left">
                                        <x-card class="!py-3">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                    {{ $goal->title }}
                                                    <x-badge :color="$goal->isCompleted() ? 'green' : 'gray'">{{ $goal->isCompleted() ? 'Completato' : 'In corso' }}</x-badge>
                                                </p>
                                                <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 text-gray-300 transition-transform" x-bind:class="expanded ? 'rotate-180' : ''" />
                                            </div>
                                            @if ($goal->isCompleted())
                                                <p class="mt-0.5 text-xs text-gray-400">Completato il {{ $goal->completed_at->translatedFormat('d M Y') }}</p>
                                            @endif
                                        </x-card>
                                    </button>

                                    <div x-show="expanded" x-cloak class="mt-2 space-y-3">
                                        <x-card class="!py-4 space-y-3 text-sm">
                                            @if ($goal->starting_point)
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Punto di partenza</p>
                                                    <p class="mt-0.5 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $goal->starting_point }}</p>
                                                </div>
                                            @endif
                                            @if ($goal->target)
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Obiettivo</p>
                                                    <p class="mt-0.5 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $goal->target }}</p>
                                                </div>
                                            @endif

                                            @if ($goal->isCompleted())
                                                <div class="border-t border-gray-100 dark:border-white/10 pt-3 space-y-3">
                                                    @if ($goal->completion_description)
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Risultato</p>
                                                            <p class="mt-0.5 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $goal->completion_description }}</p>
                                                        </div>
                                                    @endif
                                                    @if ($goal->completion_references)
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Riferimenti</p>
                                                            <p class="mt-0.5 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $goal->completion_references }}</p>
                                                        </div>
                                                    @endif
                                                    @if ($goal->completion_note)
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Nota</p>
                                                            <p class="mt-0.5 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $goal->completion_note }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($goal->notes->isNotEmpty())
                                                <div class="border-t border-gray-100 dark:border-white/10 pt-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Il filo che ci ha portato qui</p>
                                                    <div class="space-y-2">
                                                        @foreach ($goal->notes as $note)
                                                            <div class="rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                                                                <div class="flex items-center justify-between gap-2">
                                                                    <div class="flex items-center gap-2">
                                                                        <x-badge :color="$note->type === 'infortunio' ? 'red' : 'gray'">{{ $note->typeLabel() }}</x-badge>
                                                                        <span class="text-xs text-gray-400">{{ $note->created_at->translatedFormat('d M Y') }} &middot; {{ $note->author->name }}</span>
                                                                    </div>
                                                                    @if ($canManage)
                                                                        <form method="POST" action="{{ route('notes.destroy', $note) }}"
                                                                              onsubmit="return confirm('Eliminare questa nota?')">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                                                                                <x-heroicon-o-trash class="h-3.5 w-3.5" />
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                                <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $note->description }}</p>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($canManage)
                                                <div class="border-t border-gray-100 dark:border-white/10 pt-3 flex justify-end gap-4">
                                                    <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'edit-goal-{{ $goal->id }}')"
                                                            class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                                        <x-heroicon-o-pencil class="h-3.5 w-3.5" />
                                                        Modifica
                                                    </button>
                                                    <form method="POST" action="{{ route('goals.destroy', $goal) }}"
                                                          onsubmit="return confirm('Eliminare questo obiettivo? Le note collegate resteranno come note libere.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                            <x-heroicon-o-trash class="h-3.5 w-3.5" />
                                                            Elimina
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </x-card>
                                    </div>
                                </div>

                                @if ($canManage)
                                    <x-modal name="edit-goal-{{ $goal->id }}" max-width="lg">
                                        <div class="p-6" x-data="{ status: {{ Illuminate\Support\Js::from($goal->status) }} }">
                                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Modifica obiettivo</h2>

                                            <form method="POST" action="{{ route('goals.update', $goal) }}" class="mt-5 space-y-4">
                                                @csrf
                                                @method('PUT')

                                                <div class="space-y-1.5">
                                                    <x-input-label value="Titolo" />
                                                    <x-text-input name="title" value="{{ $goal->title }}" class="w-full" required />
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-input-label value="Punto di partenza" />
                                                    <textarea name="starting_point" rows="2" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ $goal->starting_point }}</textarea>
                                                </div>
                                                <div class="space-y-1.5">
                                                    <x-input-label value="Cosa si vuole raggiungere" />
                                                    <textarea name="target" rows="2" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ $goal->target }}</textarea>
                                                </div>

                                                <div class="space-y-1.5">
                                                    <x-input-label value="Stato" />
                                                    <div class="flex gap-2">
                                                        <label class="flex-1 inline-flex cursor-pointer items-center justify-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-2 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                                                            <input type="radio" name="status" value="in_progress" x-model="status" class="hidden">
                                                            In corso
                                                        </label>
                                                        <label class="flex-1 inline-flex cursor-pointer items-center justify-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-2 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                                                            <input type="radio" name="status" value="completed" x-model="status" class="hidden">
                                                            Completato
                                                        </label>
                                                    </div>
                                                </div>

                                                <div x-show="status === 'completed'" x-cloak class="space-y-4 rounded-xl bg-gray-50 dark:bg-white/5 p-4">
                                                    <div class="space-y-1.5">
                                                        <x-input-label value="Data completamento" />
                                                        <x-text-input type="date" name="completed_at" value="{{ $goal->completed_at?->toDateString() ?? now()->toDateString() }}" class="w-full" />
                                                    </div>
                                                    <div class="space-y-1.5">
                                                        <x-input-label value="Descrizione del risultato" />
                                                        <textarea name="completion_description" rows="2" class="w-full rounded-xl border-gray-200 bg-white text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ $goal->completion_description }}</textarea>
                                                    </div>
                                                    <div class="space-y-1.5">
                                                        <x-input-label value="Riferimenti (facoltativo)" />
                                                        <textarea name="completion_references" rows="2" class="w-full rounded-xl border-gray-200 bg-white text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ $goal->completion_references }}</textarea>
                                                    </div>
                                                    <div class="space-y-1.5">
                                                        <x-input-label value="Nota (facoltativo)" />
                                                        <textarea name="completion_note" rows="2" class="w-full rounded-xl border-gray-200 bg-white text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ $goal->completion_note }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="flex items-center justify-end gap-3 pt-2">
                                                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                                                    <x-primary-button>Salva</x-primary-button>
                                                </div>
                                            </form>
                                        </div>
                                    </x-modal>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($canManage)
                    <x-modal name="new-goal-{{ $enrollment->id }}" max-width="lg">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Nuovo obiettivo</h2>

                            <form method="POST" action="{{ route('enrollments.goals.store', $enrollment) }}" class="mt-5 space-y-4">
                                @csrf
                                <div class="space-y-1.5">
                                    <x-input-label value="Titolo" />
                                    <x-text-input name="title" class="w-full" required />
                                </div>
                                <div class="space-y-1.5">
                                    <x-input-label value="Punto di partenza" />
                                    <textarea name="starting_point" rows="2" placeholder="Da dove si parte..."
                                              class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100"></textarea>
                                </div>
                                <div class="space-y-1.5">
                                    <x-input-label value="Cosa si vuole raggiungere" />
                                    <textarea name="target" rows="2" placeholder="Dove si vuole arrivare..."
                                              class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100"></textarea>
                                </div>
                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                                    <x-primary-button>Aggiungi</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </x-modal>
                @endif
            </x-card>
        </div>

        <div class="min-w-0">
            <x-section-header>Note libere</x-section-header>
            <x-progress-timeline :notes="$freeNotes" :can-manage="$canManage" />
        </div>
    </div>
</div>
