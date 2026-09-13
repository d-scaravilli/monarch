@php $accent = \App\Support\ModuleTheme::classes($currentModule->color ?? 'gray'); @endphp

<x-app-layout>
    <x-slot name="header">Team</x-slot>

    <div class="flex flex-col sm:flex-row sm:items-start gap-3 mb-4">
        <x-card class="flex-1">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div class="relative flex-1 min-w-[10rem] space-y-1.5">
                    <x-input-label value="Cerca" />
                    <div class="relative">
                        <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome o email..."
                               class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
                    </div>
                </div>
                <div class="space-y-1.5">
                    <x-input-label value="Stato" />
                    <select name="status" onchange="this.form.submit()" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <option value="">Tutti</option>
                        <option value="active" @selected(request('status') === 'active')>Attivi</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inattivi</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <x-input-label value="Corso" />
                    <select name="course_id" onchange="this.form.submit()" class="rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                        <option value="">Tutti</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->discipline->name }} ({{ $course->year }})</option>
                        @endforeach
                    </select>
                </div>
                <x-year-select :years="$years" :selected="$selectedYear" />
                <button type="submit" class="rounded-xl border border-gray-200 dark:border-white/10 px-3 py-2.5 text-sm text-gray-500">Filtra</button>
                @if (request()->filled('search') || request()->filled('status') || request()->filled('course_id') || request()->has('year'))
                    <a href="{{ route('members.team') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 pb-2.5">Azzera</a>
                @endif
            </form>
        </x-card>

        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('members.create') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white shrink-0">
                <x-heroicon-o-plus class="h-4 w-4" />
                Nuovo iscritto
            </a>
        @endif
    </div>

    @if ($members->isEmpty())
        <x-card class="text-center text-gray-500 py-10">
            Nessun iscritto trovato.
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($members as $member)
                @php $initials = collect(explode(' ', $member->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''); @endphp
                <a href="{{ route('members.show', $member) }}">
                    <x-card class="h-full text-center hover:ring-gray-300 dark:hover:ring-white/20 transition">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl {{ $accent['badge'] }} text-lg font-bold text-white">
                            @if ($member->avatarUrl())
                                <img src="{{ $member->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                            @else
                                {{ mb_strtoupper($initials) }}
                            @endif
                        </div>
                        <p class="mt-3 font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $member->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $member->email }}</p>

                        @if ($member->enrollments->isNotEmpty())
                            <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                                @foreach ($member->enrollments->take(3) as $enrollment)
                                    <x-badge>{{ $enrollment->course->discipline->name }}</x-badge>
                                @endforeach
                            </div>
                        @endif
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
