<x-guest-layout>
    <div class="mb-8 text-center">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Accedi</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bentornato, inserisci le tue credenziali.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div class="space-y-1.5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input id="remember_me" type="checkbox" class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900" name="remember">
                Ricordami
            </label>
        </div>

        <x-primary-button class="w-full justify-center">
            Accedi
        </x-primary-button>
    </form>
</x-guest-layout>
