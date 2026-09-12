@php
    $initial = $enrollments->mapWithKeys(fn ($e) => [$e->id => (bool) ($attendances[$e->id] ?? false)]);
    $accentColor = $currentModule->color ?? 'gray';
    $accentHex = \App\Support\ModuleTheme::hex($accentColor);
@endphp

<x-app-layout>
    <x-slot name="header">Presenze &middot; {{ $lesson->date->translatedFormat('d M Y') }}</x-slot>

    <div class="grid gap-4 sm:grid-cols-3 mb-6">
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

        <x-card class="sm:col-span-2">
            <x-section-header>Assenti ({{ $absentees->count() }})</x-section-header>
            @if ($absentees->isEmpty())
                <p class="text-sm text-gray-500 py-4">Nessun assente.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($absentees as $absentee)
                        <x-badge color="red">{{ $absentee->name }}</x-badge>
                    @endforeach
                </div>
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
        <button
            type="button"
            @click="markAllPresent()"
            class="flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-300"
        >
            <x-heroicon-o-check-circle class="h-4 w-4" />
            Segna tutti presenti
        </button>

        <x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($enrollments as $enrollment)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                                    {{ $enrollment->user->name }}
                                    @if ($enrollment->user->notes->where('type', 'infortunio')->isNotEmpty())
                                        <x-badge color="red">infortunio</x-badge>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400">{{ $enrollment->user->email }}</p>
                            </div>
                            <x-heroicon-o-check-circle class="h-4 w-4 text-green-600" x-show="saved === {{ $enrollment->id }}" x-cloak />
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="button" @click="toggleNotes({{ $enrollment->user->id }})"
                                    class="flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                <x-heroicon-o-pencil-square class="h-4 w-4" />
                                Note ({{ $enrollment->user->notes->count() }})
                            </button>

                            <button
                                type="button"
                                @click="toggle({{ $enrollment->id }})"
                                :class="state[{{ $enrollment->id }}] ? 'bg-gray-900 dark:bg-white' : 'bg-gray-200 dark:bg-white/10'"
                                class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors"
                            >
                                <span :class="state[{{ $enrollment->id }}] ? 'translate-x-6' : 'translate-x-1'"
                                      class="inline-block h-5 w-5 transform rounded-full bg-white dark:bg-gray-900 transition-transform"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="openNotesFor === {{ $enrollment->user->id }}" x-cloak class="mt-4 rounded-xl bg-gray-50 dark:bg-white/5 p-4 space-y-4">
                        @if ($enrollment->user->notes->isNotEmpty())
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Note precedenti</p>
                                <div class="space-y-2.5 max-h-56 overflow-y-auto">
                                    @foreach ($enrollment->user->notes as $note)
                                        <div class="text-sm rounded-lg bg-white dark:bg-white/5 px-3 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <x-badge :color="$note->type === 'infortunio' ? 'red' : 'gray'">{{ $note->typeLabel() }}</x-badge>
                                                <span class="text-xs text-gray-400">{{ $note->created_at->translatedFormat('d M Y') }} &middot; {{ $note->author->name }}</span>
                                            </div>
                                            <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $note->description }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

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
                                <div class="flex justify-end">
                                    <x-secondary-button type="submit">Aggiungi</x-secondary-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-gray-500">Nessun iscritto per questo corso.</p>
            @endforelse
        </x-card>
    </div>

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
            <textarea x-model="value" @blur="save()" rows="4" placeholder="Cosa si è fatto durante la lezione..."
                      class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100"></textarea>
        </x-card>
    </div>

    <a href="{{ route('courses.show', $course) }}" class="mt-4 inline-block text-sm font-medium text-gray-500 hover:text-gray-700">&larr; Torna al corso</a>
</x-app-layout>
