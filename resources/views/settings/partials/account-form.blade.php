<x-section-header>Dati account</x-section-header>
<x-card>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('settings.account.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div class="space-y-1.5">
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" name="name" type="text" class="w-full" :value="old('name', $user->name)" required autocomplete="name" />
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-1" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Il tuo indirizzo email non è verificato.
                    <button form="send-verification" class="underline hover:text-gray-700 dark:hover:text-gray-200">
                        Invia di nuovo l'email di verifica.
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">
                        Nuovo link di verifica inviato alla tua email.
                    </p>
                @endif
            @endif
        </div>

        <x-primary-button>Salva</x-primary-button>
    </form>
</x-card>
