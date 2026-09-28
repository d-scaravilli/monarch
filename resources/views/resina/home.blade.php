<x-app-layout>
    <x-slot name="header">Home</x-slot>

    <div class="space-y-6">
        <x-card>
            <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Pennello &amp; Resina</h2>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Il tuo laboratorio per dipingere a pennello le stampe in resina, anche partendo da zero. Ricette in gocce fatte
                con i colori che hai, un percorso guidato e schede pronte per ogni progetto.
            </p>
        </x-card>

        <div>
            <x-section-header>La tua mensola</x-section-header>
            <x-card class="space-y-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $paints->flatten()->count() }} flaconi e {{ $brushCount }} pennelli.
                </p>

                @foreach ($paints as $line => $linePaints)
                    <div>
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-2">{{ $line }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($linePaints as $userPaint)
                                @php
                                    $name = $userPaint->paint?->name ?? $userPaint->name;
                                    $code = $userPaint->paint?->code ?? $userPaint->code;
                                    $hex = $userPaint->paint?->hex ?? $userPaint->hex;
                                @endphp
                                <span class="inline-flex items-center gap-2 rounded-xl bg-gray-50 dark:bg-white/5 py-1.5 pl-1.5 pr-3" title="{{ $name }} {{ $code }}">
                                    <span class="h-7 w-7 shrink-0 rounded-lg ring-1 ring-black/10 dark:ring-white/10" style="background: {{ $hex }}"></span>
                                    <span class="leading-tight">
                                        <span class="block text-xs font-medium text-gray-900 dark:text-gray-100">{{ $name }}</span>
                                        <span class="block text-[11px] text-gray-400">{{ $code }}</span>
                                    </span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </x-card>
        </div>

        <div>
            <x-section-header>Progetti</x-section-header>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    @php $theme = \App\Support\ResinaProjectTheme::for($project->theme); @endphp
                    <x-card class="p-0 overflow-hidden">
                        <div class="flex h-28 flex-col justify-between p-4" style="background: {{ $theme['band'] }}">
                            <span class="self-start rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium text-white">
                                {{ $project->status === 'completo' ? 'Completo' : 'Anteprima' }}
                            </span>
                            <h3 class="text-lg font-semibold tracking-tight" style="color: {{ $theme['accent'] }}">{{ $project->name }}</h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $project->subtitle }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $project->characters_count }} schede{{ $project->armor_types_count ? ' · '.$project->armor_types_count.' tipi di armatura' : '' }}
                            </p>
                        </div>
                    </x-card>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
