@csrf

<div class="space-y-1.5">
    <x-input-label for="name" value="Nome" />
    <x-text-input id="name" name="name" value="{{ old('name', $editUser->name ?? '') }}" class="w-full" required />
    <x-input-error :messages="$errors->get('name')" class="mt-1" />
</div>

<div class="space-y-1.5">
    <x-input-label for="email" value="Email" />
    <x-text-input id="email" type="email" name="email" value="{{ old('email', $editUser->email ?? '') }}" class="w-full" required />
    <x-input-error :messages="$errors->get('email')" class="mt-1" />
</div>

<div class="space-y-1.5">
    <x-input-label value="Ruoli" />
    <div class="flex flex-wrap gap-3 rounded-xl border border-gray-200 dark:border-white/10 p-3">
        @php $assignedRoles = ($editUser->roles ?? collect())->pluck('name'); @endphp
        @foreach ($roles as $role)
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 capitalize">
                <input type="checkbox" name="roles[]" value="{{ $role }}"
                       @checked(collect(old('roles', $assignedRoles))->contains($role))
                       class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                {{ $role }}
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('roles')" class="mt-1" />
</div>

<div class="space-y-1.5">
    <x-input-label value="Moduli" />
    <div class="flex flex-wrap gap-3 rounded-xl border border-gray-200 dark:border-white/10 p-3">
        @php $assignedModules = ($editUser->modules ?? collect())->pluck('id'); @endphp
        @foreach ($modules as $module)
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="modules[]" value="{{ $module->id }}"
                       @checked(collect(old('modules', $assignedModules))->contains($module->id))
                       class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                {{ $module->name }}
            </label>
        @endforeach
    </div>
    <p class="mt-1 text-xs text-gray-400">Gli admin vedono sempre tutti i moduli, indipendentemente da questa lista.</p>
</div>
