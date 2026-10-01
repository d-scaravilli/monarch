@props(['project'])
@php
    $theme = \App\Support\ResinaProjectTheme::for($project->theme);
    $cover = $project->cover_image_path ? Storage::disk('public')->url($project->cover_image_path) : null;
@endphp

<a href="{{ route('resina.projects.show', $project) }}" class="block">
    <x-card class="h-full overflow-hidden p-0 transition hover:ring-gray-300 dark:hover:ring-white/20">
        <div class="relative flex h-28 flex-col justify-between p-4"
             style="background: {{ $theme['band'] }}{{ $cover ? " url('{$cover}') center / cover" : '' }}">
            @if ($cover)
                <span class="absolute inset-0 bg-black/40" aria-hidden="true"></span>
            @endif
            <span class="relative self-start rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium text-white">
                {{ $project->status === 'completo' ? 'Completo' : 'Anteprima' }}
            </span>
            <h3 class="relative text-lg font-semibold tracking-tight" style="color: {{ $theme['accent'] }}">{{ $project->name }}</h3>
        </div>
        <div class="p-4">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $project->subtitle }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $project->characters_count }} schede{{ $project->armor_types_count ? ' · '.$project->armor_types_count.' tipi di armatura' : '' }}
            </p>
        </div>
    </x-card>
</a>
