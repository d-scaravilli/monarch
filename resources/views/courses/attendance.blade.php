@php
    $initial = $enrollments->mapWithKeys(fn ($e) => [$e->id => (bool) ($attendances[$e->id] ?? false)]);
    $accentColor = $currentModule->color ?? 'gray';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
    $accent = \App\Support\ModuleTheme::classes($accentColor);
@endphp

<x-app-layout>
    <x-slot name="header">Presenze &middot; {{ $lesson->date->translatedFormat('d M Y') }}</x-slot>

    @if ($lesson->cancelled)
        <x-card class="mb-6 border-2 border-dashed border-gray-300 dark:border-white/20 bg-gray-50 dark:bg-white/5">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-no-symbol class="h-6 w-6 text-gray-400 shrink-0 mt-0.5" />
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-200">Lezione annullata</p>
                        @if ($lesson->cancellation_reason)
                            <p class="mt-1 text-sm text-gray-500">{{ $lesson->cancellation_reason }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-400">Non è conteggiata nella media presenze né nel grafico andamento del corso.</p>
                    </div>
                </div>
                @if ($canManage)
                    <form method="POST" action="{{ route('courses.lessons.attendance.reactivate', [$course, $lesson]) }}">
                        @csrf
                        <x-secondary-button type="submit">Riattiva lezione</x-secondary-button>
                    </form>
                @endif
            </div>
        </x-card>
    @endif

    @unless ($lesson->cancelled)
    <div class="grid gap-4 lg:grid-cols-3 mb-6">
        <x-card class="flex flex-col items-center justify-center">
            <x-section-header class="self-start">Percentuale presenza</x-section-header>
            <div class="w-full" x-data="{
                async init() {
                    const ApexCharts = await window.loadApexCharts();
                    const isDark = document.documentElement.classList.contains('dark');
                    const chart = new ApexCharts(this.$refs.gauge, {
                        chart: { type: 'radialBar', height: 200, fontFamily: 'inherit' },
                        series: [{{ $attendanceRate }}],
                        labels: ['Presenti'],
                        colors: ['{{ $accentHex }}'],
                        plotOptions: { radialBar: { hollow: { size: '60%' }, dataLabels: { value: { fontSize: '1.4rem', fontWeight: 700, color: isDark ? '#f3f4f6' : '#111827', formatter: (v) => v + '%' } } } },
                    });
                    chart.render();
                },
            }">
                <div x-ref="gauge"></div>
            </div>
            <p class="text-xs text-gray-400 -mt-2">{{ $presentCount }}/{{ $totalEnrolled }} presenti</p>
        </x-card>

        <x-card>
            <x-section-header>Assenti ({{ $absentees->count() }})</x-section-header>
            @if ($absentees->isEmpty())
                <p class="text-sm text-gray-500 py-4">Nessun assente.</p>
            @else
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($absentees as $absentee)
                        <x-badge color="red">{{ $absentee->name }}</x-badge>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card class="p-0 overflow-hidden">
            <x-section-header class="px-5 pt-5">Presenti ({{ $presentees->count() }})</x-section-header>
            @if ($presentees->isEmpty())
                <p class="text-sm text-gray-500 px-5 pb-5 pt-1">Nessun presente.</p>
            @else
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($presentees as $presentee)
                            <tr>
                                <td class="px-5 py-2 text-gray-700 dark:text-gray-300">{{ $presentee->name }}</td>
                                <td class="px-5 py-2 text-right">
                                    <x-heroicon-o-check-circle class="h-4 w-4 text-green-600 ml-auto" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-card>
    </div>

    <div
        x-data="{
            state: {{ Illuminate\Support\Js::from($initial) }},
            saved: null,
            toggleUrl: '{{ route('courses.lessons.attendance.toggle', [$course, $lesson]) }}',
            markAllUrl: '{{ route('courses.lessons.attendance.mark-all', [$course, $lesson]) }}',
            async toggle(id) {
                this.state[id] = !this.state[id];
                this.saved = id;
                await fetch(this.toggleUrl, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                    body: JSON.stringify({ enrollment_id: id, present: this.state[id] }),
                });
                setTimeout(() => { if (this.saved === id) this.saved = null }, 900);
            },
            async markAllPresent() {
                for (const id in this.state) {
                    this.state[id] = true;
                }
                await fetch(this.markAllUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                });
            },
            openNotesFor: null,
            toggleNotes(userId) {
                this.openNotesFor = this.openNotesFor === userId ? null : userId;
            },
        }"
        class="space-y-4"
    >
        @if ($canManage)
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <button
                    type="button"
                    @click="markAllPresent()"
                    class="flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-300"
                >
                    <x-heroicon-o-check-circle class="h-4 w-4" />
                    Segna tutti presenti
                </button>

                <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'cancel-lesson')"
                        class="flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-red-600 dark:hover:text-red-400">
                    <x-heroicon-o-no-symbol class="h-4 w-4" />
                    Segna come annullata
                </button>
            </div>

            <x-modal name="cancel-lesson" max-width="md">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Annulla lezione</h2>
                    <p class="mt-1 text-sm text-gray-500">La lezione non verrà conteggiata nella media presenze né nel grafico del corso.</p>

                    <form method="POST" action="{{ route('courses.lessons.attendance.cancel', [$course, $lesson]) }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <x-input-label value="Motivo (facoltativo)" />
                            <textarea name="cancellation_reason" rows="3" placeholder="Es. maltempo, struttura chiusa..."
                                      class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100"></textarea>
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                            <x-primary-button>Conferma</x-primary-button>
                        </div>
                    </form>
                </div>
            </x-modal>

        <x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($enrollments as $enrollment)
                @php
                    $initials = collect(explode(' ', $enrollment->user->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                    $hasNoteForLesson = $enrollment->user->notes->where('lesson_id', $lesson->id)->isNotEmpty();
                @endphp
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full {{ $accent['badge'] }} text-xs font-bold text-white">
                                @if ($enrollment->user->avatarUrl())
                                    <img src="{{ $enrollment->user->avatarUrl() }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ mb_strtoupper($initials) }}
                                @endif
                            </span>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100 flex flex-wrap items-center gap-1.5">
                                    @if (auth()->user()->can('view', $enrollment->user))
                                        <a href="{{ route('members.show', $enrollment->user) }}" class="hover:underline">{{ $enrollment->user->name }}</a>
                                    @else
                                        {{ $enrollment->user->name }}
                                    @endif
                                    @if ($canManage || $enrollment->user->id === auth()->id())
                                        @if ($hasNoteForLesson)
                                            <x-badge color="red">nota</x-badge>
                                        @endif
                                        @if ($enrollment->user->notes->where('type', 'infortunio')->isNotEmpty())
                                            <x-badge color="red">infortunio</x-badge>
                                        @endif
                                    @endif
                                </p>
                            </div>
                            <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" x-show="saved === {{ $enrollment->id }}" x-cloak />
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-1.5">
                            @if ($canManage)
                                <button
                                    type="button"
                                    @click="toggle({{ $enrollment->id }})"
                                    :class="state[{{ $enrollment->id }}] ? 'bg-gray-900 dark:bg-white' : 'bg-gray-200 dark:bg-white/10'"
                                    class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors"
                                >
                                    <span :class="state[{{ $enrollment->id }}] ? 'translate-x-6' : 'translate-x-1'"
                                          class="inline-block h-5 w-5 transform rounded-full bg-white dark:bg-gray-900 transition-transform"></span>
                                </button>
                            @elseif ($initial[$enrollment->id] ?? false)
                                <x-heroicon-o-check-circle class="h-5 w-5 text-green-600" />
                            @else
                                <x-heroicon-o-x-circle class="h-5 w-5 text-gray-300 dark:text-gray-600" />
                            @endif

                            @if ($canManage || $enrollment->user->id === auth()->id())
                                <button type="button" @click="toggleNotes({{ $enrollment->user->id }})"
                                        class="flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                                    Note ({{ $enrollment->user->notes->count() }})
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($canManage || $enrollment->user->id === auth()->id())
                        <div x-show="openNotesFor === {{ $enrollment->user->id }}" x-cloak class="mt-4 rounded-xl bg-gray-50 dark:bg-white/5 p-4 space-y-4">
                            @if ($enrollment->user->notes->isNotEmpty())
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Note precedenti</p>
                                    <div class="space-y-2.5 max-h-56 overflow-y-auto">
                                        @foreach ($enrollment->user->notes as $note)
                                            <div class="text-sm rounded-lg bg-white dark:bg-white/5 px-3 py-2.5">
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
                                @php $inProgressGoals = $enrollment->goals->where('status', 'in_progress'); @endphp
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Aggiungi nota</p>
                                    <form method="POST" action="{{ route('courses.lessons.notes.store', [$course, $lesson]) }}" class="space-y-2.5">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $enrollment->user->id }}">
                                        <div class="flex flex-col sm:flex-row gap-2.5">
                                            <select name="type" class="sm:w-44 shrink-0 rounded-xl border-gray-200 bg-white text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                                @foreach (\App\Models\MemberNote::TYPES as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <textarea name="description" rows="2" required placeholder="Descrizione..."
                                                      class="flex-1 rounded-xl border-gray-200 bg-white text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100"></textarea>
                                        </div>
                                        @if ($inProgressGoals->isNotEmpty())
                                            <select name="goal_id" class="w-full rounded-xl border-gray-200 bg-white text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                                <option value="">Nessun obiettivo collegato (appunto generico)</option>
                                                @foreach ($inProgressGoals as $goal)
                                                    <option value="{{ $goal->id }}">Obiettivo: {{ $goal->title }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                        <div class="flex justify-end">
                                            <x-secondary-button type="submit">Aggiungi</x-secondary-button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-gray-500">Nessun iscritto per questo corso.</p>
            @endforelse
        </x-card>
        @endif
    </div>
    @endunless

    @if ($canManage)
        <div class="mt-4" x-data="{
            value: {{ Illuminate\Support\Js::from($lesson->description ?? '') }},
            saved: false,
            async save() {
                await fetch('{{ route('courses.lessons.description.update', [$course, $lesson]) }}', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                    body: JSON.stringify({ description: this.value }),
                });
                this.saved = true;
                setTimeout(() => (this.saved = false), 1200);
            },
        }">
            <div class="flex items-center gap-2 mb-2">
                <x-section-header class="mb-0">Descrizione lezione</x-section-header>
                <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" x-show="saved" x-cloak />
            </div>
            <x-card>
                <textarea x-model="value" rows="10" placeholder="Cosa si è fatto durante la lezione..."
                          class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100"></textarea>
                <div class="flex justify-end mt-3">
                    <x-primary-button type="button" @click="save()">Salva</x-primary-button>
                </div>
            </x-card>
        </div>
    @elseif ($lesson->description)
        <div class="mt-4">
            <x-section-header>Descrizione lezione</x-section-header>
            <x-card>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $lesson->description }}</p>
            </x-card>
        </div>
    @endif

    <a href="{{ route('courses.show', $course) }}"
       class="mt-6 mb-4 inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5">
        <x-heroicon-o-arrow-left class="h-4 w-4" />
        Torna {{ $course->isEvento() ? "all'evento" : 'al corso' }}
    </a>
</x-app-layout>
