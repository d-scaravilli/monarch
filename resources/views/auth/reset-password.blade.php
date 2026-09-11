<x-guest-layout>
    <div class="mb-8 text-center">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Reimposta password</h1>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="space-y-1.5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="password" value="Nuova password" />
            <x-text-input id="password" class="w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="password_confirmation" value="Conferma nuova password" />
            <x-text-input id="password_confirmation" class="w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <x-primary-button class="w-full justify-center">
            Reimposta password
        </x-primary-button>
    </form>
</x-guest-layout>
