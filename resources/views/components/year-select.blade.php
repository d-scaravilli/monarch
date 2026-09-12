@props(['years', 'selected' => null, 'name' => 'year', 'allLabel' => 'Tutti gli anni'])

<div class="space-y-1.5">
    <x-input-label value="Anno" />
    <select name="{{ $name }}" onchange="this.form.submit()"
            {{ $attributes->class(['rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100']) }}>
        @if ($allLabel)
            <option value="">{{ $allLabel }}</option>
        @endif
        @foreach ($years as $year)
            <option value="{{ $year }}" @selected($selected === $year)>{{ $year }}</option>
        @endforeach
    </select>
</div>
