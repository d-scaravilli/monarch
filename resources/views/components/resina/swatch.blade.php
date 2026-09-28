@props(['hex', 'type' => 'normal', 'size' => 'h-7 w-7'])

{{-- A bottle's color. Metallic paints get the shiny gradient from color.js. --}}
<span {{ $attributes->class(['inline-block shrink-0 rounded-lg ring-1 ring-black/10 dark:ring-white/10', $size]) }}
      style="background: {{ $hex }}"
      @if (in_array($type, ['metallic', 'airbrush'], true))
          x-data x-bind:style="Resina.color.hexStyle({{ Illuminate\Support\Js::from($hex) }}, true)"
      @endif
></span>
