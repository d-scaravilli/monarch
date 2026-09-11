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
    </div>
</x-app-layout>
