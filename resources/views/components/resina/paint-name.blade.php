@props(['name', 'code'])

{{-- A paint's name is never shown without its code. --}}
<span {{ $attributes }}>{{ $name }} <small class="text-[11px] font-normal text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ $code }}</small></span>
