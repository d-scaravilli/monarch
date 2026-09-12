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

<div class="space-y-1.5">
    <x-input-label value="Equipaggiamento" />
    <div class="space-y-2.5 rounded-xl border border-gray-200 dark:border-white/10 p-3" x-data="{ borrowed: {{ old('has_borrowed_equipment', optional($member->memberProfile)->has_borrowed_equipment ?? false) ? 'true' : 'false' }} }">
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="hidden" name="owns_sword" value="0">
            <input type="checkbox" name="owns_sword" value="1" @checked(old('owns_sword', optional($member->memberProfile)->owns_sword))
                   class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
            Spada propria
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="hidden" name="shirt_given" value="0">
            <input type="checkbox" name="shirt_given" value="1" @checked(old('shirt_given', optional($member->memberProfile)->shirt_given))
                   class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
            Maglietta dell'anno in corso consegnata
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="hidden" name="has_borrowed_equipment" value="0">
            <input type="checkbox" name="has_borrowed_equipment" value="1" x-model="borrowed"
                   class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
            Ha attrezzatura in prestito
        </label>
        <div x-show="borrowed" x-cloak class="pl-6">
            <x-text-input name="borrowed_equipment_notes" placeholder="Cosa è in prestito"
                          value="{{ old('borrowed_equipment_notes', optional($member->memberProfile)->borrowed_equipment_notes) }}" class="w-full" />
        </div>
    </div>
</div>
