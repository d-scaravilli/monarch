@props(['project', 'theme', 'label' => null])
@php $cover = $project->cover_image_path ? Storage::disk('public')->url($project->cover_image_path) : null; @endphp

{{-- The project's themed header band (the one place a project keeps its own look). --}}
<div {{ $attributes->class(['relative overflow-hidden rounded-2xl px-5 py-6 sm:px-8 sm:py-8']) }}
     style="background: {{ $theme['band'] }}{{ $cover ? " url('{$cover}') center / cover" : '' }}">
    @if ($cover)
        <span class="absolute inset-0 bg-black/45" aria-hidden="true"></span>
    @endif
    @if ($theme['stars'])
        <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-70" viewBox="0 0 1200 300" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <g fill="#fff"><circle cx="80" cy="60" r="1.6"/><circle cx="210" cy="120" r="1"/><circle cx="330" cy="40" r="1.3"/><circle cx="520" cy="90" r="1"/><circle cx="690" cy="30" r="1.5"/><circle cx="760" cy="240" r="1"/><circle cx="40" cy="250" r="1.2"/></g>
            <g stroke="#F6E4B0" stroke-width="1" opacity=".6" fill="none"><polyline points="860,60 930,100 1010,80 1060,140 1000,190 930,170 930,100"/><line x1="1060" y1="140" x2="1140" y2="180"/></g>
            <g fill="#F6E4B0"><circle cx="860" cy="60" r="3"/><circle cx="930" cy="100" r="3"/><circle cx="1010" cy="80" r="3.5"/><circle cx="1060" cy="140" r="3"/><circle cx="1000" cy="190" r="3"/><circle cx="930" cy="170" r="2.5"/><circle cx="1140" cy="180" r="3"/></g>
        </svg>
    @endif
    <div class="relative max-w-3xl">
        @if ($label)
            <span class="inline-block rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium text-white/80">{{ $label }}</span>
        @endif
        {{ $slot }}
    </div>
</div>
