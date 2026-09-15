<x-section-header>Foto profilo</x-section-header>
<x-card>
    <form method="post" action="{{ route('settings.avatar.update') }}" enctype="multipart/form-data" class="flex items-center gap-5"
          x-data="{ preview: null }">
        @csrf

        <span class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gray-900 dark:bg-white/10 text-white text-xl font-semibold">
            <template x-if="preview">
                <img :src="preview" class="h-full w-full object-cover" alt="">
            </template>
            <template x-if="!preview">
                <span>
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" class="h-16 w-16 object-cover" alt="">
                    @else
                        {{ Str::of($user->name)->substr(0, 1)->upper() }}
                    @endif
                </span>
            </template>
        </span>

        <div class="flex-1">
            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                <x-heroicon-o-camera class="h-4 w-4" />
                Scegli immagine
                <input type="file" name="avatar" accept="image/*" class="hidden"
                       @change="preview = URL.createObjectURL($event.target.files[0]); $el.closest('form').requestSubmit()">
            </label>
            <x-input-error :messages="$errors->get('avatar')" class="mt-1" />
            <p class="mt-1.5 text-xs text-gray-400">JPG o PNG, max 2MB.</p>
        </div>
    </form>

    @if ($user->avatarUrl())
        <form method="post" action="{{ route('settings.avatar.destroy') }}" class="mt-3" onsubmit="return confirm('Eliminare la foto profilo? Torneranno le iniziali.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400">
                <x-heroicon-o-trash class="h-4 w-4" />
                Elimina foto
            </button>
        </form>
    @endif
</x-card>
