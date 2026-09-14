@php
    $colorHexMap = collect($colorChoices)->mapWithKeys(fn ($c) => [$c => \App\Support\ModuleTheme::hex($c)]);
@endphp

<x-app-layout>
    <x-slot name="header">Aspetto</x-slot>

    <div class="max-w-2xl space-y-6">
        @unless ($gdAvailable)
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-700 dark:text-amber-400 ring-1 ring-amber-100 dark:ring-amber-500/20">
                L'estensione GD di PHP non è attiva su questo server: non è possibile generare le icone dell'applicazione da qui.
            </div>
        @endunless

        <x-card
            x-data="{
                mode: {{ Illuminate\Support\Js::from(old('icon_mode', $setting->icon_mode)) }},
                letter: {{ Illuminate\Support\Js::from(old('icon_letter', $setting->icon_letter)) }},
                color: {{ Illuminate\Support\Js::from(old('icon_color', $setting->icon_color)) }},
                colorHex: {{ Illuminate\Support\Js::from($colorHexMap) }},
                imagePreviewUrl: {{ Illuminate\Support\Js::from($setting->iconImageUrl()) }},
                onImageChange(event) {
                    const file = event.target.files[0];
                    if (! file) return;
                    const reader = new FileReader();
                    reader.onload = () => { this.imagePreviewUrl = reader.result; };
                    reader.readAsDataURL(file);
                },
            }"
        >
            <form method="POST" action="{{ route('admin.appearance.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-5">
                    <div class="shrink-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Anteprima</p>
                        <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-2xl text-4xl font-bold text-white shadow-sm"
                             :style="mode === 'letter' ? 'background-color: ' + colorHex[color] : ''">
                            <template x-if="mode === 'letter'">
                                <span x-text="(letter || '?').charAt(0).toUpperCase()"></span>
                            </template>
                            <template x-if="mode === 'image'">
                                <img :src="imagePreviewUrl" alt="" class="h-full w-full object-cover" x-show="imagePreviewUrl">
                            </template>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Questa icona sostituisce quella dell'app nella schermata Home (PWA), nella tab del browser e
                        nella scheda di anteprima su iOS/Android. Non tocca le icone dei singoli moduli.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <x-input-label value="Modalità" />
                    <div class="flex flex-wrap gap-2">
                        <label class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-1.5 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                            <input type="radio" name="icon_mode" value="letter" x-model="mode" class="hidden">
                            Lettera + colore
                        </label>
                        <label class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-1.5 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                            <input type="radio" name="icon_mode" value="image" x-model="mode" class="hidden">
                            Immagine caricata
                        </label>
                    </div>
                </div>

                <div x-show="mode === 'letter'" x-cloak class="space-y-5">
                    <div class="space-y-1.5">
                        <x-input-label for="icon_letter" value="Carattere" />
                        <x-text-input id="icon_letter" name="icon_letter" x-model="letter" maxlength="2" class="w-20 text-center text-lg font-bold" />
                        <x-input-error :messages="$errors->get('icon_letter')" class="mt-1" />
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label value="Colore di sfondo" />
                        <div class="flex flex-wrap gap-2">
                            @foreach ($colorChoices as $color)
                                @php $c = \App\Support\ModuleTheme::classes($color); @endphp
                                <label class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full ring-2 ring-transparent has-[:checked]:ring-gray-900 dark:has-[:checked]:ring-white">
                                    <input type="radio" name="icon_color" value="{{ $color }}" x-model="color" class="hidden">
                                    <span class="h-7 w-7 rounded-full {{ $c['badge'] }}"></span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('icon_color')" class="mt-1" />
                    </div>
                </div>

                <div x-show="mode === 'image'" x-cloak class="space-y-1.5">
                    <x-input-label value="Immagine" />
                    <p class="text-xs text-gray-400">Viene ritagliata al centro in un quadrato e ridimensionata per ogni formato necessario.</p>
                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                        <x-heroicon-o-photo class="h-4 w-4" />
                        Carica immagine
                        <input type="file" name="icon_image" accept="image/*" class="hidden" x-on:change="onImageChange($event)">
                    </label>
                    <x-input-error :messages="$errors->get('icon_image')" class="mt-1" />
                </div>

                @if ($gdAvailable)
                    <x-primary-button>Salva e genera le icone</x-primary-button>
                @endif
            </form>
        </x-card>

        <p class="text-xs text-gray-400">
            Nota: chi ha già installato l'app sulla schermata Home del telefono potrebbe dover rimuoverla e
            reinstallarla per vedere l'icona nuova — è il dispositivo a tenerne una copia in cache, non qualcosa
            che possiamo forzare da qui.
        </p>
    </div>
</x-app-layout>
