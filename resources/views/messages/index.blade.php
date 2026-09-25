@php
    $initialView = $errors->any() ? 'compose' : ($contact ? 'thread' : 'empty');
    $initials = fn ($name) => mb_strtoupper(collect(explode(' ', $name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''));
@endphp

<x-app-layout>
    <x-slot name="header">Messaggi</x-slot>

    <div x-data="{ view: {{ Illuminate\Support\Js::from($initialView) }}, search: '' }">
        <div class="grid gap-4 lg:grid-cols-3 items-start">
            {{-- List panel: one row per person, not per message --}}
            <div :class="view === 'empty' ? 'block' : 'hidden lg:block'" class="lg:col-span-1 space-y-3">
                @if ($canSend)
                    <button type="button" @click="view = 'compose'"
                            class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
                        <x-heroicon-o-pencil-square class="h-4 w-4" /> Nuovo messaggio
                    </button>
                @endif

                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input type="text" x-model="search" placeholder="Cerca per nome..."
                           class="w-full rounded-xl border-gray-200 bg-gray-50 pl-9 focus:bg-white focus:border-gray-900 focus:ring-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
                </div>

                <x-card class="p-0 divide-y divide-gray-100 dark:divide-white/10 lg:max-h-[70vh] lg:overflow-y-auto">
                    @forelse ($conversations as $conv)
                        @php
                            $person = $conv['counterpart'];
                            $last = $conv['thread']->first();
                            $isActive = $contact && $contact->id === $person->id;
                            $personDeleted = $person->trashed();
                        @endphp
                        <a href="{{ route('messages.show', $person) }}"
                           x-show="!search || {{ Illuminate\Support\Js::from(\Illuminate\Support\Str::lower($person->name)) }}.includes(search.toLowerCase())"
                           class="flex items-center gap-3 px-4 py-3.5 {{ $isActive ? 'bg-gray-50 dark:bg-white/5' : '' }}">
                            <span class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-900 dark:bg-white/10 text-xs font-bold text-white">
                                @if ($person->avatarUrl())
                                    <img src="{{ $person->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                                @else
                                    {{ $initials($person->name) }}
                                @endif
                                @if ($conv['unread'])
                                    <span class="absolute -top-0.5 -right-0.5 h-3 w-3 rounded-full bg-orange-500 ring-2 ring-white dark:ring-gray-900"></span>
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-sm {{ $conv['unread'] ? 'font-semibold text-gray-900 dark:text-gray-100' : 'font-medium text-gray-700 dark:text-gray-300' }}">
                                        {{ $person->name }}
                                        @if ($personDeleted)
                                            <span class="font-normal text-gray-400">(eliminato)</span>
                                        @endif
                                    </p>
                                    <span class="shrink-0 text-xs text-gray-400">{{ $conv['latestAt']->translatedFormat('d M') }}</span>
                                </div>
                                <p class="truncate text-sm text-gray-400">
                                    @if ($last['direction'] === 'sent')
                                        <span class="text-gray-400">Tu:</span>
                                    @endif
                                    {{ $last['message']->subject }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-gray-500">Nessuna conversazione.</p>
                    @endforelse
                </x-card>
            </div>

            {{-- Detail panel: selected thread, or the composer --}}
            <div :class="view === 'empty' ? 'hidden lg:flex' : 'flex'" class="lg:col-span-2 flex-col gap-3">
                <button type="button" @click="view = 'empty'" class="lg:hidden self-start inline-flex items-center gap-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-chevron-left class="h-4 w-4" /> Messaggi
                </button>

                @if ($contact && $thread)
                    <div x-show="view === 'thread'" x-cloak>
                        <x-card class="!p-0 overflow-hidden">
                            <div class="flex items-center gap-3 border-b border-gray-100 dark:border-white/10 px-5 py-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-900 dark:bg-white/10 text-xs font-bold text-white">
                                    @if ($contact->avatarUrl())
                                        <img src="{{ $contact->avatarUrl() }}" class="h-full w-full object-cover" alt="">
                                    @else
                                        {{ $initials($contact->name) }}
                                    @endif
                                </span>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $contact->name }}
                                    @if ($contact->trashed())
                                        <span class="font-normal text-gray-400">(eliminato)</span>
                                    @endif
                                </p>
                            </div>

                            <div class="divide-y divide-gray-100 dark:divide-white/10 max-h-[65vh] overflow-y-auto">
                                @foreach ($thread as $entry)
                                    @php
                                        $canDeleteMessage = auth()->user()->hasRole('admin') || $entry['message']->sender_id === auth()->id();
                                    @endphp
                                    <div class="px-5 py-4">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $entry['direction'] === 'sent' ? 'Tu' : $contact->name }}
                                                <span class="font-normal text-gray-400">&middot; {{ $entry['message']->subject }}</span>
                                            </p>
                                            <span class="flex shrink-0 items-center gap-2">
                                                <span class="text-xs text-gray-400">{{ $entry['at']->translatedFormat('d M Y, H:i') }}</span>
                                                @if ($canDeleteMessage)
                                                    <form method="POST" action="{{ route('messages.destroy', $entry['message']) }}" onsubmit="return confirm('Eliminare questo messaggio?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-gray-400 hover:text-red-600">
                                                            <x-heroicon-o-trash class="h-3.5 w-3.5" />
                                                        </button>
                                                    </form>
                                                @endif
                                            </span>
                                        </div>
                                        <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $entry['message']->body }}</p>
                                        @if ($entry['direction'] === 'sent')
                                            <p class="mt-1.5 flex items-center gap-1 text-xs {{ $entry['read_at'] ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                                @if ($entry['read_at'])
                                                    <x-heroicon-o-check-circle class="h-3.5 w-3.5" /> Letto il {{ $entry['read_at']->translatedFormat('d M Y, H:i') }}
                                                @else
                                                    Non letto
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </x-card>
                    </div>
                @endif

                @if ($canSend)
                    <div x-show="view === 'compose'" x-cloak x-data="{ mode: {{ Illuminate\Support\Js::from(old('recipient_mode', 'single')) }} }">
                        <x-card>
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
                                                {{ $course->displayName() }} ({{ $course->year }})
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
                                    <x-secondary-button type="button" @click="view = {{ Illuminate\Support\Js::from($contact ? 'thread' : 'empty') }}">Annulla</x-secondary-button>
                                    <x-primary-button>Invia</x-primary-button>
                                </div>
                            </form>
                        </x-card>
                    </div>
                @endif

                <x-card x-show="view === 'empty'" x-cloak class="hidden lg:flex flex-col items-center justify-center py-16 text-center text-gray-400">
                    <x-heroicon-o-envelope class="h-10 w-10 mb-3" />
                    <p class="text-sm">Seleziona una conversazione per leggerla.</p>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
