@props(['value'])

{{-- Closeness badge, from an Alpine expression giving [kind, label] (color.closeness()). --}}
<span class="rounded-full px-2 py-0.5 text-[11px] font-medium"
      :class="{ ok: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400', mid: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400', far: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' }[({{ $value }})[0]]"
      x-text="({{ $value }})[1]"></span>
