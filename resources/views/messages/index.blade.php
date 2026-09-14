<x-app-layout>
    <x-slot name="header">Messaggi</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($canSend)
                <div class="inline-flex gap-1 rounded-xl bg-gray-100 dark:bg-white/5 p-1 text-sm font-medium">
                    <a href="{{ route('messages.index') }}" class="rounded-lg px-4 py-2 transition {{ $box === 'received' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">Ricevuti</a>
                    <a href="{{ route('messages.index', ['box' => 'sent']) }}" class="rounded-lg px-4 py-2 transition {{ $box === 'sent' ? 'bg-white dark:bg-gray-900 shadow-sm text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">Inviati</a>
                </div>
                <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'compose-message')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
                    <x-heroicon-o-pencil-square class="h-4 w-4" /> Nuovo messaggio
                </button>
            @else
                <div></div>
            @endif
        </div>

        <div class="grid gap-4 lg:grid-cols-3 items-start">
            {{-- List --}}
            <div class="lg:col-span-1">
                <x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10 max-h-[32rem] lg:max-h-[70vh] overflow-y-auto">
                    @forelse ($list as $item)
                        @php
                            $msg = $box === 'sent' ? $item : $item->message;
                            $isSelected = $selectedMessage && $selectedMessage->id === $msg->id;
                            $isUnread = $box === 'received' && ! $item->read_at;
                        @endphp
                        <a href="{{ route('messages.show', $msg) }}"
                           class="block px-4 py-3.5 {{ $isSelected ? 'bg-gray-50 dark:bg-white/5' : '' }}">
                            <div class="flex items-start gap-2">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $isUnread ? 'bg-orange-500' : '' }}"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm {{ $isUnread ? 'font-semibold text-gray-900 dark:text-gray-100' : 'font-medium text-gray-700 dark:text-gray-300' }}">
                                            {{ $box === 'sent' ? $msg->subject : $msg->sender->name }}
                                        </p>
                                        <span class="shrink-0 text-xs text-gray-400">{{ $msg->created_at->translatedFormat('d M') }}</span>
                                    </div>
                                    <p class="truncate text-sm {{ $isUnread ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400' }}">
                                        {{ $box === 'sent' ? \Illuminate\Support\Str::limit($msg->body, 60) : $msg->subject }}
                                    </p>
                                    @if ($box === 'sent')
                                        <p class="mt-1 text-xs text-gray-400">{{ $item->read_recipients_count }}/{{ $item->recipients_count }} letti</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-gray-500">Nessun messaggio.</p>
                    @endforelse
                </x-card>
            </div>

            {{-- Detail --}}
            <div class="lg:col-span-2">
                @if ($selectedMessage)
                    <x-card>
                        <div class="border-b border-gray-100 dark:border-white/10 pb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $selectedMessage->subject }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Da {{ $selectedMessage->sender->name }} &middot; {{ $selectedMessage->created_at->translatedFormat('d M Y, H:i') }}
                            </p>
                        </div>
                        <p class="mt-4 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $selectedMessage->body }}</p>

                        @if ($readReceipts)
                            <div class="mt-6 border-t border-gray-100 dark:border-white/10 pt-4">
                                <x-section-header>Destinatari ({{ $readReceipts->count() }})</x-section-header>
                                <div class="space-y-2">
                                    @foreach ($readReceipts as $receipt)
                                        <div class="flex items-center justify-between gap-2 text-sm">
                                            <span class="text-gray-700 dark:text-gray-300">{{ $receipt->user->name }}</span>
                                            @if ($receipt->read_at)
                                                <span class="shrink-0 inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                                    <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
                                                    Letto il {{ $receipt->read_at->translatedFormat('d M Y, H:i') }}
                                                </span>
                                            @else
                                                <span class="shrink-0 text-xs text-gray-400">Non letto</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </x-card>
                @else
                    <x-card class="flex flex-col items-center justify-center py-16 text-center text-gray-400">
                        <x-heroicon-o-envelope class="h-10 w-10 mb-3" />
                        <p class="text-sm">Seleziona un messaggio per leggerlo.</p>
                    </x-card>
                @endif
            </div>
        </div>
    </div>

    @if ($canSend)
        <x-modal name="compose-message" max-width="lg" :show="$errors->any()">
            <div class="p-6" x-data="{ mode: {{ Illuminate\Support\Js::from(old('recipient_mode', 'single')) }} }">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Nuovo messaggio</h2>

                <form method="POST" action="{{ route('messages.store') }}" class="mt-5 space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <x-input-label value="Destinatari" />
                        <div class="flex flex-wrap gap-2">
                            <label class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-1.5 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                                <input type="radio" name="recipient_mode" value="single" x-model="mode" class="hidden">
                                Singolo
                            </label>
                            <label class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-1.5 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                                <input type="radio" name="recipient_mode" value="multiple" x-model="mode" class="hidden">
                                Più utenti
                            </label>
                            <label class="inline-flex cursor-pointer items-center rounded-xl border border-gray-200 dark:border-white/10 px-3 py-1.5 text-sm font-medium has-[:checked]:border-gray-900 has-[:checked]:bg-gray-100 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white/10">
                                <input type="radio" name="recipient_mode" value="course" x-model="mode" class="hidden">
                                Corso/evento intero
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('recipient_mode')" class="mt-1" />
                    </div>

                    <div x-show="mode === 'single'" x-cloak>
                        <select name="recipient_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="">Seleziona...</option>
                            @foreach ($recipientPool as $person)
                                <option value="{{ $person->id }}" @selected(old('recipient_id') == $person->id)>
                                    {{ $person->name }}{{ $person->hasRole('instructor') ? ' — istruttore' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('recipient_id')" class="mt-1" />
                    </div>

                    <div x-show="mode === 'multiple'" x-cloak class="max-h-48 overflow-y-auto rounded-xl border border-gray-200 dark:border-white/10 p-3 space-y-2">
                        @forelse ($recipientPool as $person)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="recipient_ids[]" value="{{ $person->id }}"
                                       @checked(collect(old('recipient_ids', []))->contains($person->id))
                                       class="rounded-md border-gray-300 text-gray-900 focus:ring-gray-900">
                                {{ $person->name }}{{ $person->hasRole('instructor') ? ' — istruttore' : '' }}
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">Nessun destinatario disponibile.</p>
                        @endforelse
                        <x-input-error :messages="$errors->get('recipient_ids')" class="mt-1" />
                    </div>

                    <div x-show="mode === 'course'" x-cloak>
                        <select name="course_id" class="w-full rounded-xl border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                            <option value="">Seleziona...</option>
                            @foreach ($coursePool as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>
                                    {{ $course->discipline->name }} ({{ $course->year }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('course_id')" class="mt-1" />
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label value="Oggetto" />
                        <x-text-input name="subject" value="{{ old('subject') }}" class="w-full" required />
                        <x-input-error :messages="$errors->get('subject')" class="mt-1" />
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label value="Messaggio" />
                        <textarea name="body" rows="5" required
                                  class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">{{ old('body') }}</textarea>
                        <x-input-error :messages="$errors->get('body')" class="mt-1" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Annulla</x-secondary-button>
                        <x-primary-button>Invia</x-primary-button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endif
</x-app-layout>
