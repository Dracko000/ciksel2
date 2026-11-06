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
    /* Chip ikon lembut: latar pastel tipis + ikon berwarna. Blok gradien
       pekat terasa berat di dashboard yang penuh kartu. */
    $iconToneClass = match ($iconTone) {
        'success' => 'bg-emerald-50 text-emerald-600 ring-1 ring-inset ring-emerald-100',
        'warning' => 'bg-amber-50 text-amber-600 ring-1 ring-inset ring-amber-100',
        'danger' => 'bg-bahaya-50 text-bahaya-700 ring-1 ring-inset ring-bahaya-100',
        'neutral' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
        'brand' => 'bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100',
        default => 'bg-sky-50 text-sky-600 ring-1 ring-inset ring-sky-100',
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
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $iconToneClass }}">
                    {!! $icon !!}
                </span>
            @endif

            @if ($badge)
                <span class="badge {{ $badgeToneClass }}">{{ $badge }}</span>
            @endif
        </div>
    @endif

    @if ($title)
        <p class="eyebrow mb-1.5">{{ $title }}</p>
    @endif

    <div class="text-slate-900">
        {{ $slot }}
    </div>
</div>