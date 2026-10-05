@props(['title' => null, 'iconTone' => 'info', 'badge' => null, 'badgeTone' => 'neutral'])

{{--
    Kartu statistik/metrik.

    Ikon dikirim sebagai named slot sehingga markup SVG benar-benar dirender
    dan bukan ikut ter-escape:

        <x-card title="Total Siswa">
            <x-slot name="icon"><x-icon name="users" class="h-5 w-5" /></x-slot>
            790
        </x-card>
--}}

@php
    /* Default memakai info, bukan brand: sehingga stat card yang tidak
       menyebut tone tidak lagi merah semua. Merah dicadangkan untuk
       "kamu di sini" dan aksi utama. */
    $iconToneClass = match ($iconTone) {
        'success' => 'bg-sukses-50 text-sukses-700',
        'warning' => 'bg-peringatan-50 text-peringatan-700',
        'danger' => 'bg-bahaya-50 text-bahaya-700',
        'neutral' => 'bg-slate-100 text-slate-500',
        'brand' => 'bg-brand-50 text-brand-600',
        default => 'bg-info-50 text-info-700',
    };

    $badgeToneClass = match ($badgeTone) {
        'success' => 'bg-sukses-50 text-sukses-700',
        'warning' => 'bg-peringatan-50 text-peringatan-700',
        'danger' => 'bg-bahaya-50 text-bahaya-800',
        'brand' => 'bg-brand-50 text-brand-700',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    @if ($icon || $badge)
        <div class="mb-4 flex items-start justify-between gap-3">
            @if (isset($icon))
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $iconToneClass }}">
                    {!! $icon !!}
                </span>
            @endif

            @if ($badge)
                <span class="badge {{ $badgeToneClass }}">{{ $badge }}</span>
            @endif
        </div>
    @endif

    @if ($title)
        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $title }}</p>
    @endif

    <div class="text-slate-900">
        {{ $slot }}
    </div>
</div>