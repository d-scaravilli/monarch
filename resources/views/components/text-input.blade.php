@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-gray-900 rounded-xl shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-100 dark:focus:bg-gray-900 dark:focus:border-white/30']) }}>
