@csrf

@php
    $initialSchedules = ($course->schedules ?? collect())->map(fn ($s) => [
        'weekday' => $s->weekday,
        'start_time' => substr($s->start_time, 0, 5),
        'end_time' => substr($s->end_time, 0, 5),
    ])->values();
    $courseType = old('type', $course->type ?? 'corso');
@endphp

<div x-data="{ type: {{ Illuminate\Support\Js::from($courseType) }}, schedules: {{ Illuminate\Support\Js::from($initialSchedules) }}, eventDates: [] }" class="space-y-6">
    <div class="space-y-2">
        <x-input-label value="Tipo" />
        <div class="flex gap-2">
            <button type="button" @click="type = 'corso'"
                    :class="type === 'corso' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300'"
                    class="flex-1 rounded-xl px-4 py-3 text-sm font-semibold transition">
                Corso
            </button>
            <button type="button" @click="type = 'evento'"
                    :class="type === 'evento' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300'"
                    class="flex-1 rounded-xl px-4 py-3 text-sm font-semibold transition">
                Evento
            </button>
            <input type="hidden" name="type" :value="type">
        </div>
        <p class="text-xs text-gray-400">Corso: periodico, con fasce orarie ricorrenti. Evento: uno o più giorni specifici, senza fasce ricorrenti.</p>
        <x-input-error :messages="$errors->get('type')" class="mt-1" />
    </div>

    <div x-show="type === 'evento'" x-cloak class="space-y-2">
        <x-input-label for="title" value="Titolo evento" />
        <x-text-input id="title" name="title" value="{{ old('title', $course->title ?? '') }}" class="w-full py-3 text-base" placeholder="Es. Stage estivo di Aikido" />
        <p class="text-xs text-gray-400">Obbligatorio: è il nome con cui l'evento viene mostrato, al posto della disciplina.</p>
        <x-input-error :messages="$errors->get('title')" class="mt-1" />
    </div>

    <div x-data="{ newDiscipline: false }" class="space-y-2">
        <x-input-label for="discipline_id" value="Disciplina" />
        <template x-if="!newDiscipline">
            <div class="flex gap-2">
                <select id="discipline_id" name="discipline_id" class="flex-1 rounded-xl border-gray-200 bg-gray-50 py-3 text-base focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">Seleziona...</option>
                    @foreach ($disciplines as $discipline)
                        <option value="{{ $discipline->id }}" @selected(old('discipline_id', $course->discipline_id ?? null) == $discipline->id)>{{ $discipline->name }}</option>
                    @endforeach
                </select>
                <button type="button" @click="newDiscipline = true" class="rounded-xl border border-gray-200 dark:border-white/10 px-4 text-sm text-gray-500 shrink-0">Nuova</button>
            </div>
        </template>
        <template x-if="newDiscipline">
            <div class="flex gap-2">
                <x-text-input name="new_discipline" placeholder="Nome disciplina" class="flex-1 !w-auto min-w-0 py-3 text-base" />
                <button type="button" @click="newDiscipline = false" class="rounded-xl border border-gray-200 dark:border-white/10 px-4 text-sm text-gray-500 shrink-0">Annulla</button>
            </div>
        </template>
        <x-input-error :messages="$errors->get('discipline_id')" class="mt-1" />
        <x-input-error :messages="$errors->get('new_discipline')" class="mt-1" />
    </div>

    <div x-data="{ newRoom: false }" class="space-y-2">
        <x-input-label for="room_id" value="Sala" />
        <template x-if="!newRoom">
            <div class="flex gap-2">
                <select id="room_id" name="room_id" class="flex-1 rounded-xl border-gray-200 bg-gray-50 py-3 text-base focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    <option value="">Seleziona...</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(old('room_id', $course->room_id ?? null) == $room->id)>{{ $room->name }} ({{ $room->capacity }} posti)</option>
                    @endforeach
                </select>
                <button type="button" @click="newRoom = true" class="rounded-xl border border-gray-200 dark:border-white/10 px-4 text-sm text-gray-500 shrink-0">Nuova</button>
            </div>
        </template>
        <template x-if="newRoom">
            <div class="flex gap-2">
                <x-text-input name="new_room_name" placeholder="Nome sala" class="flex-1 !w-auto min-w-0 py-3 text-base" />
                <x-text-input type="number" min="1" name="new_room_capacity" placeholder="Posti" class="!w-24 shrink-0 py-3 text-base" />
                <button type="button" @click="newRoom = false" class="rounded-xl border border-gray-200 dark:border-white/10 px-4 text-sm text-gray-500 shrink-0">Annulla</button>
            </div>
        </template>
        <x-input-error :messages="$errors->get('room_id')" class="mt-1" />
        <x-input-error :messages="$errors->get('new_room_name')" class="mt-1" />
        <x-input-error :messages="$errors->get('new_room_capacity')" class="mt-1" />
    </div>

    <div class="space-y-2">
        <x-input-label value="Icona" />
        <p class="text-xs text-gray-400">Facoltativa: se non scelta, viene usata l'icona del modulo.</p>
        <div class="grid grid-cols-8 gap-2">
            <label class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-gray-200 dark:border-white/10 has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                <input type="radio" name="icon" value="" class="hidden" @checked(old('icon', $course->icon ?? null) === null)>
                <x-heroicon-o-no-symbol class="h-5 w-5 text-gray-400" />
            </label>
            @foreach (\App\Support\CourseIcons::choices() as $icon)
                <label class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-gray-200 dark:border-white/10 has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                    <input type="radio" name="icon" value="{{ $icon }}" class="hidden" @checked(old('icon', $course->icon ?? null) === $icon)>
                    <x-course-icon :icon="$icon" class="h-5 w-5 text-gray-600 dark:text-gray-300" />
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('icon')" class="mt-1" />
    </div>

    <div class="space-y-2">
        <x-input-label for="year" value="Anno" />
        <x-text-input id="year" name="year" value="{{ old('year', $course->year ?? '2025/2026') }}" class="w-full py-3 text-base" placeholder="Es. 2025/2026 oppure 18-19-20 settembre 2026" />
        <x-input-error :messages="$errors->get('year')" class="mt-1" />
    </div>

    <div class="space-y-2">
        <x-input-label for="description" value="Descrizione" />
        <textarea id="description" name="description" rows="3"
                  class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 text-base focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('description', $course->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-1" />
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div class="space-y-2">
            <x-input-label for="monthly_cost" value="Costo mensile (€)" />
            <x-text-input id="monthly_cost" type="number" step="0.01" min="0" name="monthly_cost" value="{{ old('monthly_cost', $course->monthly_cost ?? '') }}" class="w-full py-3 text-base" />
            <x-input-error :messages="$errors->get('monthly_cost')" class="mt-1" />
        </div>
        <div class="space-y-2">
            <x-input-label for="annual_cost" value="Costo annuale (€)" />
            <x-text-input id="annual_cost" type="number" step="0.01" min="0" name="annual_cost" value="{{ old('annual_cost', $course->annual_cost ?? '') }}" class="w-full py-3 text-base" />
            <x-input-error :messages="$errors->get('annual_cost')" class="mt-1" />
        </div>
        <div class="space-y-2">
            <x-input-label for="enrollment_cost" value="Costo iscrizione (una tantum)" />
            <x-text-input id="enrollment_cost" type="number" step="0.01" min="0" name="enrollment_cost" value="{{ old('enrollment_cost', $course->enrollment_cost ?? '') }}" class="w-full py-3 text-base" placeholder="Facoltativo" />
            <x-input-error :messages="$errors->get('enrollment_cost')" class="mt-1" />
        </div>
    </div>

    <div class="space-y-2">
        <x-input-label value="Istruttori" />
        <div class="space-y-2.5 rounded-xl border border-gray-200 dark:border-white/10 p-4">
            @forelse ($instructors as $instructor)
                @php $assigned = ($course->instructors ?? collect())->pluck('id'); @endphp
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="instructors[]" value="{{ $instructor->id }}"
                           @checked(collect(old('instructors', $assigned))->contains($instructor->id))
                           class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                    {{ $instructor->name }}
                </label>
            @empty
                <p class="text-sm text-gray-400">Nessun istruttore disponibile.</p>
            @endforelse
        </div>
    </div>

    <div x-show="type === 'corso'" x-cloak class="space-y-2">
        <x-input-label value="Fasce orarie ricorrenti" />
        <p class="text-xs text-gray-400">Usate da "Genera lezioni" per sapere in quali giorni creare le lezioni.</p>

        <div class="space-y-2" x-show="schedules.length > 0">
            <template x-for="(schedule, index) in schedules" :key="index">
                <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4">
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="space-y-1">
                            <x-input-label value="Giorno" class="text-xs" />
                            <select :name="`schedules[${index}][weekday]`" x-model.number="schedule.weekday"
                                    class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                                @foreach (\App\Models\CourseSchedule::WEEKDAYS as $i => $day)
                                    <option value="{{ $i }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1">
                            <x-input-label value="Dalle" class="text-xs" />
                            <input type="time" :name="`schedules[${index}][start_time]`" x-model="schedule.start_time"
                                   class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        </div>
                        <div class="space-y-1">
                            <x-input-label value="Alle" class="text-xs" />
                            <input type="time" :name="`schedules[${index}][end_time]`" x-model="schedule.end_time"
                                   class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        </div>
                        <button type="button" @click="schedules.splice(index, 1)" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 ml-auto">
                            <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <p class="text-sm text-gray-400" x-show="schedules.length === 0" x-cloak>Nessuna fascia oraria configurata.</p>

        <button type="button" @click="schedules.push({ weekday: 1, start_time: '18:00', end_time: '19:00' })"
                class="flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 mt-1">
            <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi fascia oraria
        </button>
    </div>

    <div x-show="type === 'evento'" x-cloak class="space-y-2">
        <x-input-label value="Date evento" />

        @if ($course->exists)
            <p class="text-sm text-gray-400">
                Le date di un evento già creato si gestiscono dalla pagina del corso ("Nuova lezione singola" per aggiungerne, l'elenco lezioni per rimuoverle).
            </p>
        @else
            <p class="text-xs text-gray-400">Uno o più giorni specifici in cui si svolge l'evento.</p>

            <div class="space-y-2" x-show="eventDates.length > 0">
                <template x-for="(date, index) in eventDates" :key="index">
                    <div class="flex items-center gap-2">
                        <input type="date" :name="`event_dates[${index}]`" x-model="eventDates[index]"
                               class="flex-1 rounded-xl border-gray-200 bg-gray-50 py-3 text-base dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <button type="button" @click="eventDates.splice(index, 1)" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10">
                            <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                    </div>
                </template>
            </div>

            <p class="text-sm text-gray-400" x-show="eventDates.length === 0" x-cloak>Nessuna data aggiunta.</p>

            <button type="button" @click="eventDates.push('')"
                    class="flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 mt-1">
                <x-heroicon-o-plus class="h-4 w-4" /> Aggiungi data
            </button>
            <x-input-error :messages="$errors->get('event_dates')" class="mt-1" />
        @endif
    </div>
</div>
