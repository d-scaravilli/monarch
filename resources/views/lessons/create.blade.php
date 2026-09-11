<x-app-layout>
    <x-slot name="header">Nuova lezione</x-slot>

    <x-card class="max-w-lg">
        <form method="POST" action="{{ route('lessons.store') }}" class="space-y-5">
            @csrf

            <div class="space-y-1.5">
                <x-input-label for="course_id" value="Corso" />
                <select id="course_id" name="course_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(old('course_id', $selectedCourseId) == $course->id)>{{ $course->discipline->name }} ({{ $course->year }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('course_id')" class="mt-1" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="date" value="Data" />
                <x-text-input id="date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="w-full" />
                <x-input-error :messages="$errors->get('date')" class="mt-1" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="note" value="Nota (opzionale)" />
                <x-text-input id="note" name="note" value="{{ old('note') }}" class="w-full" />
                <x-input-error :messages="$errors->get('note')" class="mt-1" />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Aggiungi lezione</x-primary-button>
                <a href="{{ route('lessons.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
