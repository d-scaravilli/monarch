<x-section-header>Password</x-section-header>
<x-card>
    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div class="space-y-1.5">
            <x-input-label for="update_password_current_password" value="Password attuale" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="update_password_password" value="Nuova password" />
            <x-text-input id="update_password_password" name="password" type="password" class="w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="update_password_password_confirmation" value="Conferma nuova password" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
        </div>

        <x-primary-button>Aggiorna password</x-primary-button>
    </form>
</x-card>
