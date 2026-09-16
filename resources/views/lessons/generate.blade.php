@php
    $scheduleByCourse = $courses->mapWithKeys(fn ($course) => [
        $course->id => $course->schedules->map(fn ($s) => $s->weekdayLabel().' '.substr($s->start_time, 0, 5).'-'.substr($s->end_time, 0, 5))->implode(', '),
    ]);
@endphp

<x-app-layout>
    <x-slot name="header">Genera lezioni</x-slot>

    <x-card class="max-w-lg" x-data="{ courseId: '{{ old('course_id', $selectedCourseId) }}', schedules: {{ Illuminate\Support\Js::from($scheduleByCourse) }} }">
        <form method="POST" action="{{ route('lessons.generate.store') }}" class="space-y-5">
            @csrf

            <div class="space-y-1.5">
                <x-input-label for="course_id" value="Corso" />
                <select id="course_id" name="course_id" x-model="courseId" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->discipline->name }} ({{ $course->year }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('course_id')" class="mt-1" />
            </div>

            <div class="rounded-xl bg-gray-50 dark:bg-white/5 px-4 py-3 text-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Fasce orarie del corso</p>
                <template x-for="(label, id) in schedules" :key="id">
                    <p x-show="String(courseId) === String(id)" x-text="label || 'Nessuna fascia oraria configurata — aggiungila nella pagina del corso prima di generare.'" class="text-gray-700 dark:text-gray-300"></p>
                </template>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <x-input-label for="start_date" value="Dal" />
                    <x-text-input id="start_date" type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" class="w-full" required />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                </div>
                <div class="space-y-1.5">
                    <x-input-label for="end_date" value="Al" />
                    <x-text-input id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" class="w-full" required />
                    <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                </div>
            </div>

            <p class="text-xs text-gray-400">Genera una lezione per ogni giorno, nell'intervallo scelto, che cade in una delle fasce orarie configurate per il corso.</p>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Genera lezioni</x-primary-button>
                <a href="{{ route('lessons.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Annulla</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
