<x-guest-layout>
    <div class="mb-8 text-center">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Crea account</h1>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div class="space-y-1.5">
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" class="w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="password_confirmation" value="Conferma password" />
            <x-text-input id="password_confirmation" class="w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <x-primary-button class="w-full justify-center">
            Crea account
        </x-primary-button>

        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Hai già un account?
            <a class="font-medium text-gray-700 dark:text-gray-300 hover:underline" href="{{ route('login') }}">Accedi</a>
        </p>
    </form>
</x-guest-layout>
