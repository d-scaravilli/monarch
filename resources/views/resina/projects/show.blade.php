@php $accent = \App\Support\ModuleTheme::classes($currentModule?->color); @endphp

<x-app-layout>
    <x-slot name="header">{{ $project->name }}</x-slot>

    <x-resina.payload id="resina-project" :data="$payload" />

    <div x-data="resinaProjectPage('resina-project')" class="space-y-4">
        <a href="{{ route('resina.projects.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-heroicon-o-arrow-left class="h-4 w-4" /> Progetti
        </a>

        <x-resina.project-band :project="$project" :theme="$theme" :label="$project->status === 'completo' ? 'Progetto' : 'Anteprima'">
            <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl" style="color: {{ $theme['accent'] }}">{{ $project->name }}</h2>
            @if ($project->intro)
                <p class="mt-2 text-sm text-white/80">{{ $project->intro }}</p>
            @endif
            @if ($isAdmin)
                <a href="{{ route('resina.projects.edit', $project) }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-white/15 px-3 py-1.5 text-sm font-medium text-white hover:bg-white/25">
                    <x-heroicon-o-pencil-square class="h-4 w-4" /> Modifica progetto
                </a>
            @endif
        </x-resina.project-band>

        <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('resina.projects.show', [$project, $key]) }}"
                   class="shrink-0 rounded-xl px-3.5 py-2 text-sm font-medium transition {{ $key === $tab ? $accent['badge'].' text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        @if ($tab === 'personaggi')
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="-mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <template x-for="g in groups" :key="g.slug">
                        <button type="button" @click="group = g.slug"
                                class="shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium transition"
                                :class="group === g.slug ? '{{ $accent['badge'] }} text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10'"
                                x-text="g.name"></button>
                    </template>
                </div>
                @if ($isAdmin)
                    <a href="{{ route('resina.characters.create', $project) }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">
                        <x-heroicon-o-plus class="h-4 w-4" /> Nuovo personaggio
                    </a>
                @endif
            </div>

            <div class="relative">
                <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input type="search" x-model.debounce.150ms="query" placeholder="Cerca un personaggio o una costellazione"
                       class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:border-gray-900 focus:bg-white focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100" />
            </div>

            @foreach ($project->groups as $group)
                <div x-show="charactersOf({{ Illuminate\Support\Js::from($group->slug) }}).length">
                    <x-section-header>{{ $group->name }}</x-section-header>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        <template x-for="c in charactersOf({{ Illuminate\Support\Js::from($group->slug) }})" :key="c.slug">
                            <x-resina.character-card />
                        </template>
                    </div>
                </div>
            @endforeach
            <div x-show="ungrouped.length">
                <x-section-header>Senza gruppo</x-section-header>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    <template x-for="c in ungrouped" :key="c.slug">
                        <x-resina.character-card />
                    </template>
                </div>
            </div>
            <p x-show="visibleCount === 0" x-cloak class="py-8 text-center text-sm text-gray-500">Nessun personaggio trovato.</p>
        @elseif ($tab === 'armature')
            <div class="flex flex-wrap items-start justify-between gap-3">
                <p class="max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                    Le armature per tipo. Ogni scheda personaggio rimanda qui, e le procedure complete sono in «Passo passo».
                </p>
                @if ($isAdmin)
                    <a href="{{ route('resina.armor-types.create', $project) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">
                        <x-heroicon-o-plus class="h-4 w-4" /> Nuova armatura
                    </a>
                @endif
            </div>
            @foreach ($project->armorTypes as $armor)
                <x-card>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $armor->title }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $armor->who }}</p>
                        </div>
                        @if ($isAdmin)
                            <a href="{{ route('resina.armor-types.edit', [$project, $armor]) }}" title="Modifica armatura" class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5">
                                <x-heroicon-o-pencil-square class="h-5 w-5" />
                            </a>
                        @endif
                    </div>
                    @if ($armor->description)
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $armor->description }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($armor->recipes as $recipe)
                            <a href="{{ route('resina.recipes.index') }}#r-{{ $recipe->slug }}"
                               class="inline-flex items-center gap-2 rounded-full bg-gray-50 py-1 pl-1 pr-3 text-sm text-gray-700 hover:bg-gray-100 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                                <span class="h-5 w-5 rounded-full ring-1 ring-black/10" :style="recipeDot({{ $recipe->id }})"></span>
                                {{ $recipe->title }}
                            </a>
                        @endforeach
                    </div>
                    @if ($armor->guide)
                        <a href="{{ route('resina.projects.show', [$project, 'passo']) }}#g-{{ $armor->guide->slug }}"
                           class="mt-3 inline-flex rounded-xl border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                            Procedura passo passo
                        </a>
                    @endif
                </x-card>
            @endforeach
        @elseif ($tab === 'passo')
            @if ($isAdmin)
                <div class="flex justify-end">
                    <a href="{{ route('resina.guides.create', $project) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">
                        <x-heroicon-o-plus class="h-4 w-4" /> Nuova guida
                    </a>
                </div>
            @endif
            @foreach ($project->guides as $guide)
                <x-card id="g-{{ $guide->slug }}" class="scroll-mt-24">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $guide->title }}</h3>
                        @if ($isAdmin)
                            <a href="{{ route('resina.guides.edit', [$project, $guide]) }}" title="Modifica guida" class="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5">
                                <x-heroicon-o-pencil-square class="h-5 w-5" />
                            </a>
                        @endif
                    </div>
                    @if ($guide->intro)
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $guide->intro }}</p>
                    @endif
                    <ol class="mt-3 space-y-3">
                        <template x-for="(step, i) in guideSteps[{{ Illuminate\Support\Js::from($guide->slug) }}]" :key="i">
                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="i + 1"></span>
                                <div class="min-w-0">
                                    <p class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="step.title"></span>
                                        <template x-if="step.brush">
                                            <a :href="step.brush.href" :title="step.brush.title" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300">🖌 <span x-text="step.brush.label"></span></a>
                                        </template>
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="step.description"></p>
                                    <x-resina.ingredients list="step.ingredients" class="mt-1" />
                                </div>
                            </li>
                        </template>
                    </ol>
                </x-card>
            @endforeach
        @elseif ($tab === 'ricette')
            <p class="text-sm text-gray-500 dark:text-gray-400">Tutte le ricette usate in questo progetto, in un solo posto.</p>
            <template x-for="r in recipes" :key="r.slug">
                <x-resina.recipe-card />
            </template>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Link per trovare immagini di riferimento. Ogni scheda personaggio ha anche i suoi link specifici.
            </p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($project->links as $link)
                    <a href="{{ $link->url }}" target="_blank" rel="noopener">
                        <x-card class="h-full transition hover:ring-gray-300 dark:hover:ring-white/20">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $link->title }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $link->description }}</p>
                        </x-card>
                    </a>
                @empty
                    <p class="text-sm text-gray-500">Nessun riferimento.</p>
                @endforelse
            </div>
        @endif
    </div>
</x-app-layout>
