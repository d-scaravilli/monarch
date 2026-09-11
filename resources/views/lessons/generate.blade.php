<x-app-layout>
    <x-slot name="header">Genera lezioni</x-slot>

    <x-card class="max-w-lg">
        <form method="POST" action="{{ route('lessons.generate.store') }}" class="space-y-5">
            @csrf

            <div class="space-y-1.5">
                <x-input-label for="course_id" value="Corso" />
                <select id="course_id" name="course_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->discipline->name }} ({{ $course->year }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('course_id')" class="mt-1" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="weekday" value="Giorno della settimana" />
                <select id="weekday" name="weekday" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    @foreach (['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'] as $i => $day)
                        <option value="{{ $i }}" @selected(old('weekday', 1) == $i)>{{ $day }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('weekday')" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <x-input-label for="start_date" value="Dal" />
                    <x-text-input id="start_date" type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" class="w-full" />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                </div>
                <div class="space-y-1.5">
                    <x-input-label for="end_date" value="Al" />
                    <x-text-input id="end_date" type="date" name="end_date" value="{{ old('end_date', now()->addMonths(9)->toDateString()) }}" class="w-full" />
                    <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                </div>
            </div>

            <div class="space-y-1.5">
                <x-input-label for="note" value="Nota (opzionale)" />
                <x-text-input id="note" name="note" value="{{ old('note') }}" class="w-full" />
                <x-input-error :messages="$errors->get('note')" class="mt-1" />
            </div>

            <p class="text-xs text-gray-400">Esempio: "ogni Lunedì da settembre a giugno" genera una lezione per ogni lunedì compreso nell'intervallo.</p>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Genera lezioni</x-primary-button>
                <a href="{{ route('lessons.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
