<x-guest-layout>
    <div class="mb-8 text-center">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Password dimenticata</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Inserisci la tua email: ti manderemo un link per reimpostarla.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div class="space-y-1.5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <x-primary-button class="w-full justify-center">
            Invia link di reset
        </x-primary-button>
    </form>
</x-guest-layout>
