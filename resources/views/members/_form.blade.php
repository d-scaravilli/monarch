@csrf

<div class="space-y-1.5">
    <x-input-label for="name" value="Nome e cognome" />
    <x-text-input id="name" name="name" value="{{ old('name', $member->name ?? '') }}" class="w-full" required />
    <x-input-error :messages="$errors->get('name')" class="mt-1" />
</div>

<div class="space-y-1.5">
    <x-input-label for="email" value="Email" />
    <x-text-input id="email" type="email" name="email" value="{{ old('email', $member->email ?? '') }}" class="w-full" required />
    <x-input-error :messages="$errors->get('email')" class="mt-1" />
</div>

<div class="space-y-1.5">
    <x-input-label for="fiscal_code" value="Codice fiscale" />
    <x-text-input id="fiscal_code" name="fiscal_code" value="{{ old('fiscal_code', optional($member->memberProfile)->fiscal_code) }}" class="w-full" />
    <x-input-error :messages="$errors->get('fiscal_code')" class="mt-1" />
</div>

<div class="space-y-1.5">
    <x-input-label for="emergency_contact" value="Contatto di emergenza" />
    <x-text-input id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact', optional($member->memberProfile)->emergency_contact) }}" class="w-full" />
    <x-input-error :messages="$errors->get('emergency_contact')" class="mt-1" />
</div>

<div class="space-y-1.5">
    <x-input-label for="notes" value="Note" />
    <textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('notes', optional($member->memberProfile)->notes) }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
</div>
