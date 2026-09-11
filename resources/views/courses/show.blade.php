@php
    $canManageAttendance = auth()->user()->can('manageAttendance', $course);
    $payments = $course->enrollments->flatMap(fn ($e) => $e->payments->map(fn ($p) => tap($p, fn ($p) => $p->enrollment = $e)));
@endphp

<x-app-layout>
    <x-slot name="header">{{ $course->discipline->name }}</x-slot>

    <div class="space-y-6">
        @if ($course->trashed())
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-700 dark:text-amber-400 ring-1 ring-amber-100 dark:ring-amber-500/20">
                Questo corso è stato eliminato. Stai consultando lo storico.
            </div>
        @endif

        <x-card>
            <div class="flex items-start justify-between gap-3">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <span class="flex items-center gap-1.5"><x-heroicon-o-home class="h-4 w-4" /> {{ $course->room->name }}</span>
                    <span class="flex items-center gap-1.5"><x-heroicon-o-calendar-days class="h-4 w-4" /> {{ $course->year }}</span>
                    <span class="flex items-center gap-1.5"><x-heroicon-o-credit-card class="h-4 w-4" /> &euro;{{ number_format($course->monthly_cost, 2) }}/mese &middot; &euro;{{ number_format($course->annual_cost, 2) }}/anno</span>
                </div>

                @if ($canManage && ! $course->trashed())
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('courses.edit', $course) }}" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                            <x-heroicon-o-pencil class="h-4 w-4" />
                        </a>
                        <form method="POST" action="{{ route('courses.destroy', $course) }}" onsubmit="return confirm('Eliminare questo corso?{{ $course->enrollments->count() || $course->lessons->count() ? ' Ha '.$course->enrollments->count().' iscritti e '.$course->lessons->count().' lezioni collegate: i dati restano consultabili nello storico.' : '' }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            @if ($course->instructors->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($course->instructors as $instructor)
                        <x-badge>{{ $instructor->name }}</x-badge>
                    @endforeach
                </div>
            @endif
        </x-card>

        <div>
            <x-section-header>Iscritti ({{ $course->enrollments->count() }})</x-section-header>
            <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                @forelse ($course->enrollments as $enrollment)
                    <x-swipe-row :action="route('enrollments.destroy', $enrollment)">
                        <a href="{{ route('members.show', $enrollment->user) }}" class="flex items-center justify-between px-5 py-3.5">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $enrollment->user->email }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($enrollment->discount > 0)
                                    <x-badge color="amber">-&euro;{{ number_format($enrollment->discount, 2) }}</x-badge>
                                @endif
                                <x-badge :color="$enrollment->status === 'active' ? 'green' : 'gray'">{{ $enrollment->status }}</x-badge>
                            </div>
                        </a>
                    </x-swipe-row>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-500">Nessun iscritto.</p>
                @endforelse
            </x-card>

            @if ($canManage && ! $course->trashed())
                <div class="mt-3" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-1">
                        <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi iscritto
                    </button>
                    <x-card x-show="open" x-cloak class="mt-3">
                        <form method="POST" action="{{ route('courses.enrollments.store', $course) }}" class="flex flex-wrap items-end gap-3">
                            @csrf
                            <div class="flex-1 min-w-[10rem] space-y-1.5">
                                <x-input-label value="Iscritto" />
                                <select name="user_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                    @forelse ($availableMembers as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @empty
                                        <option value="">Nessun iscritto disponibile</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="w-28 space-y-1.5">
                                <x-input-label value="Sconto &euro;" />
                                <x-text-input type="number" step="0.01" min="0" name="discount" value="0" class="w-full" />
                            </div>
                            <x-primary-button>Iscrivi</x-primary-button>
                        </form>
                    </x-card>
                </div>
            @endif
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <x-section-header class="mb-0">Lezioni</x-section-header>
                @if ($canManage && ! $course->trashed())
                    <div class="flex items-center gap-3 text-sm font-medium text-gray-600 dark:text-gray-300">
                        <a href="{{ route('lessons.create', ['course_id' => $course->id]) }}" class="flex items-center gap-1"><x-heroicon-o-plus class="h-4 w-4" /> Lezione</a>
                        <a href="{{ route('lessons.generate') }}" class="flex items-center gap-1"><x-heroicon-o-square-3-stack-3d class="h-4 w-4" /> Genera</a>
                    </div>
                @endif
            </div>
            <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                @forelse ($course->lessons as $lesson)
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-calendar-days class="h-4 w-4 text-gray-400" />
                            {{ $lesson->date->translatedFormat('d M Y') }}
                            @if ($lesson->date->isFuture())
                                <x-badge color="amber">in programma</x-badge>
                            @endif
                        </div>

                        @if ($canManageAttendance)
                            <a href="{{ route('courses.lessons.attendance.edit', [$course, $lesson]) }}"
                               class="flex items-center gap-1.5 text-sm font-medium text-gray-900 dark:text-gray-100 hover:underline">
                                <x-heroicon-o-clipboard-document-check class="h-4 w-4" />
                                Presenze
                            </a>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-gray-500">Nessuna lezione programmata.</p>
                @endforelse
            </x-card>
        </div>

        @if ($canManage)
            <div>
                <x-section-header>Pagamenti</x-section-header>
                <x-card class="divide-y divide-gray-100 dark:divide-white/10 p-0">
                    @forelse ($payments->sortByDesc('date') as $payment)
                        <x-swipe-row :action="route('payments.destroy', $payment)">
                            <div class="flex items-center justify-between px-5 py-3.5">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $payment->enrollment->user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $payment->date->translatedFormat('d M Y') }} &middot; {{ $payment->method }}</p>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">&euro;{{ number_format($payment->amount, 2) }}</span>
                            </div>
                        </x-swipe-row>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nessun pagamento registrato.</p>
                    @endforelse
                </x-card>

                @if ($course->enrollments->isNotEmpty() && ! $course->trashed())
                    <div class="mt-3" x-data="{ open: false, url: '' }">
                        <button type="button" @click="open = !open" class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-1">
                            <x-heroicon-o-plus class="h-4 w-4" /> Registra pagamento
                        </button>
                        <x-card x-show="open" x-cloak class="mt-3">
                            <form method="POST" :action="url" class="flex flex-wrap items-end gap-3">
                                @csrf
                                <div class="flex-1 min-w-[10rem] space-y-1.5">
                                    <x-input-label value="Iscritto" />
                                    <select @change="url = $event.target.value" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                        <option value="">Seleziona...</option>
                                        @foreach ($course->enrollments as $enrollment)
                                            <option value="{{ route('enrollments.payments.store', $enrollment) }}">{{ $enrollment->user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-24 space-y-1.5">
                                    <x-input-label value="Importo &euro;" />
                                    <x-text-input type="number" step="0.01" min="0" name="amount" class="w-full" />
                                </div>
                                <div class="w-32 space-y-1.5">
                                    <x-input-label value="Metodo" />
                                    <select name="method" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                        <option value="contanti">Contanti</option>
                                        <option value="bonifico">Bonifico</option>
                                        <option value="carta">Carta</option>
                                    </select>
                                </div>
                                <div class="w-36 space-y-1.5">
                                    <x-input-label value="Data" />
                                    <x-text-input type="date" name="date" value="{{ now()->toDateString() }}" class="w-full" />
                                </div>
                                <x-primary-button>Registra</x-primary-button>
                            </form>
                        </x-card>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
