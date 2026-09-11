<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Conferma password</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Questa è un'area protetta: conferma la password per continuare.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div class="space-y-1.5">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <x-primary-button class="w-full justify-center">
            Conferma
        </x-primary-button>
    </form>
</x-guest-layout>
