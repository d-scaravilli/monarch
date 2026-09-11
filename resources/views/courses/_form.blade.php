@csrf

<div x-data="{ newDiscipline: false }" class="space-y-1.5">
    <x-input-label for="discipline_id" value="Disciplina" />
    <template x-if="!newDiscipline">
        <div class="flex gap-2">
            <select id="discipline_id" name="discipline_id" class="flex-1 rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                <option value="">Seleziona...</option>
                @foreach ($disciplines as $discipline)
                    <option value="{{ $discipline->id }}" @selected(old('discipline_id', $course->discipline_id ?? null) == $discipline->id)>{{ $discipline->name }}</option>
                @endforeach
            </select>
            <button type="button" @click="newDiscipline = true" class="rounded-xl border border-gray-200 dark:border-white/10 px-3 text-sm text-gray-500">Nuova</button>
        </div>
    </template>
    <template x-if="newDiscipline">
        <div class="flex gap-2">
            <x-text-input name="new_discipline" placeholder="Nome disciplina" class="flex-1" />
            <button type="button" @click="newDiscipline = false" class="rounded-xl border border-gray-200 dark:border-white/10 px-3 text-sm text-gray-500">Annulla</button>
        </div>
    </template>
    <x-input-error :messages="$errors->get('discipline_id')" class="mt-1" />
    <x-input-error :messages="$errors->get('new_discipline')" class="mt-1" />
</div>

<div class="space-y-1.5">
    <x-input-label for="room_id" value="Sala" />
    <select id="room_id" name="room_id" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
        @foreach ($rooms as $room)
            <option value="{{ $room->id }}" @selected(old('room_id', $course->room_id ?? null) == $room->id)>{{ $room->name }} ({{ $room->capacity }} posti)</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('room_id')" class="mt-1" />
</div>

<div class="space-y-1.5">
    <x-input-label for="year" value="Anno" />
    <x-text-input id="year" name="year" value="{{ old('year', $course->year ?? '2025/2026') }}" class="w-full" />
    <x-input-error :messages="$errors->get('year')" class="mt-1" />
</div>

<div class="grid grid-cols-2 gap-4">
    <div class="space-y-1.5">
        <x-input-label for="monthly_cost" value="Costo mensile (&euro;)" />
        <x-text-input id="monthly_cost" type="number" step="0.01" min="0" name="monthly_cost" value="{{ old('monthly_cost', $course->monthly_cost ?? '') }}" class="w-full" />
        <x-input-error :messages="$errors->get('monthly_cost')" class="mt-1" />
    </div>
    <div class="space-y-1.5">
        <x-input-label for="annual_cost" value="Costo annuale (&euro;)" />
        <x-text-input id="annual_cost" type="number" step="0.01" min="0" name="annual_cost" value="{{ old('annual_cost', $course->annual_cost ?? '') }}" class="w-full" />
        <x-input-error :messages="$errors->get('annual_cost')" class="mt-1" />
    </div>
</div>

<div class="space-y-1.5">
    <x-input-label value="Istruttori" />
    <div class="space-y-2 rounded-xl border border-gray-200 dark:border-white/10 p-3">
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
