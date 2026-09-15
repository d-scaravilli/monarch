@props(['class' => '', 'openUpward' => false, 'openRight' => false])

@php
    $recentNotifications = auth()->user()->notifications()->latest()->limit(3)->get();
    $unreadCount = auth()->user()->unreadNotifications()->count();
@endphp

<div class="relative" x-data="{ open: false, unread: {{ $unreadCount }} }">
    <button
        type="button"
        @click="
            open = !open;
            if (open && unread > 0) {
                fetch('{{ route('notifications.read') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                });
                unread = 0;
            }
        "
        @click.outside="open = false"
        {{ $attributes->class(['relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300']) }}
    >
        <x-heroicon-o-bell class="h-5 w-5" />
        <span
            x-show="unread > 0" x-cloak
            x-text="unread > 9 ? '9+' : unread"
            class="absolute top-0.5 right-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-gray-950"
        ></span>
    </button>

    <div
        x-show="open" x-cloak
        x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="absolute {{ $openRight ? 'left-0' : 'right-0' }} z-30 {{ $openUpward ? 'bottom-full mb-2' : 'top-full mt-2' }} w-80 max-w-[85vw] overflow-hidden rounded-2xl bg-white dark:bg-gray-900 shadow-lg ring-1 ring-gray-100 dark:ring-white/10"
    >
        <p class="px-4 pt-3 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Notifiche</p>
        <div class="divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($recentNotifications as $notification)
                <a href="{{ $notification->data['url'] ?? '#' }}" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $notification->data['title'] ?? 'Notifica' }}</p>
                    @if (! empty($notification->data['body']))
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $notification->data['body'] }}</p>
                    @endif
                    <p class="mt-1 text-[11px] text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500">Nessuna notifica.</p>
            @endforelse
        </div>
    </div>
</div>
