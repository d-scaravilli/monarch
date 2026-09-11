@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-gray-900 rounded-xl shadow-sm']) }}>
