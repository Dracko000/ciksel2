@props(['title' => null, 'iconTone' => 'brand', 'badge' => null, 'badgeTone' => 'neutral'])

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
    $iconToneClass = match ($iconTone) {
        'success' => 'bg-emerald-50 text-emerald-600',
        'warning' => 'bg-amber-50 text-amber-600',
        'danger' => 'bg-rose-50 text-rose-600',
        'neutral' => 'bg-slate-100 text-slate-500',
        default => 'bg-brand-50 text-brand-600',
    };

    $badgeToneClass = match ($badgeTone) {
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-700',
        'danger' => 'bg-rose-50 text-rose-700',
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