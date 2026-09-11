<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Verifica la tua email</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Abbiamo inviato un link di verifica al tuo indirizzo email. Se non l'hai ricevuto, possiamo inviartene un altro.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <p class="mb-4 text-sm font-medium text-green-600 dark:text-green-400 text-center">
            Nuovo link di verifica inviato.
        </p>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>Invia di nuovo</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                Esci
            </button>
        </form>
    </div>
</x-guest-layout>
