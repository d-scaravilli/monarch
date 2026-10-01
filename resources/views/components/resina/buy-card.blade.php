@props(['suggestion'])

{{-- "Per il colore esatto": a suggested paint the user doesn't own, from an Alpine expression. --}}
<div {{ $attributes->class(['flex items-start gap-3 rounded-xl bg-gray-50 p-3 text-sm dark:bg-white/5']) }}>
    <span class="h-8 w-8 shrink-0 rounded-lg ring-1 ring-black/10" :style="'background:' + {{ $suggestion }}.hex"></span>
    <div class="min-w-0 text-gray-700 dark:text-gray-200">
        <b>Per il colore esatto:</b> Vallejo Game Color <span x-text="{{ $suggestion }}.name"></span>
        <small class="text-[11px] text-gray-400" x-text="{{ $suggestion }}.code"></small>.
        <span x-text="{{ $suggestion }}.why"></span>
        <form method="POST" :action="{{ $suggestion }}.buyUrl" class="mt-2">
            @csrf
            <button type="submit" class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium hover:bg-white dark:border-white/10 dark:hover:bg-white/10">L'ho comprato</button>
        </form>
    </div>
</div>
