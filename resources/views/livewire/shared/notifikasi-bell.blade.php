<div x-data="{ open: false }" wire:poll.60s="refreshUnreadCount" class="relative">
    <button type="button" @click="open = !open" @keydown.escape.window="open = false"
            class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
            :aria-expanded="open" aria-haspopup="true" aria-label="Notifikasi">
        <x-icon name="bell" class="h-5 w-5" />

        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full
                         bg-brand-600 px-1 text-[10px] font-semibold text-white ring-2 ring-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-cloak x-show="open" @click.outside="open = false" x-transition.origin.top.right
         class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-semibold text-slate-800">Notifikasi</p>

            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllRead"
                        class="text-xs font-medium text-brand-600 transition hover:text-brand-700">
                    Tandai semua dibaca
                </button>
            @endif
        </div>

        <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            @forelse ($notifications as $notification)
                @php $payload = $notification->data; @endphp

                <a href="{{ $payload['url'] ?? '#' }}" @click="open = false"
                   class="flex items-start gap-3 px-4 py-3 transition hover:bg-slate-50">
                    <span @class([
                        'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                        'bg-slate-200' => $notification->read_at,
                        'bg-brand-500' => ! $notification->read_at,
                    ])></span>

                    <div class="min-w-0">
                        <p @class([
                            'text-sm leading-snug',
                            'font-semibold text-slate-800' => ! $notification->read_at,
                            'font-medium text-slate-600' => $notification->read_at,
                        ])>{{ $payload['judul'] ?? 'Notifikasi' }}</p>

                        @if (! empty($payload['pesan']))
                            <p class="mt-0.5 break-words text-xs text-slate-500">{{ $payload['pesan'] }}</p>
                        @endif

                        <p class="mt-1 text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <x-icon name="bell" class="mx-auto h-8 w-8 text-slate-300" />
                    <p class="mt-2 text-sm text-slate-500">Belum ada notifikasi</p>
                </div>
            @endforelse
        </div>
    </div>
</div>