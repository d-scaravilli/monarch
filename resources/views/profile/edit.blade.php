<x-app-layout>
    <x-slot name="header">{{ __('Profilo') }}</x-slot>

    <div class="max-w-xl space-y-4">
        <x-card>
            @include('profile.partials.update-profile-information-form')
        </x-card>

        <x-card>
            @include('profile.partials.update-password-form')
        </x-card>

        <x-card>
            @include('profile.partials.delete-user-form')
        </x-card>

        {{-- Desktop already has logout in the sidebar footer; mobile has no other way out. --}}
        <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
            @csrf
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-gray-100">
                <x-heroicon-o-arrow-right-start-on-rectangle class="h-5 w-5" />
                Esci
            </button>
        </form>
    </div>
</x-app-layout>
