@props([
    'hadir' => 0,
    'terlambat' => 0,
    'tidak' => 0,
    'total' => 0,
    'title' => 'Kehadiran',
    'subtitle' => null,
    'tidakLabel' => 'Tidak Hadir',
    'totalLabel' => 'Total',
    'satuan' => 'orang',
])

@php
    // Persentase & geometri donut dihitung di sini supaya pemanggil cukup
    // mengirim angka mentah. total dijaga minimal 1 agar tidak bagi nol.
    //
    // Tiga porsi digambar sebagai tiga busur di atas cincin latar:
    // hijau = tepat waktu, kuning = terlambat, sisanya (cincin latar) = alpha.
    // "Hadir" pada persentase mencakup yang tepat waktu maupun terlambat.
    $totalAman = max(1, (int) $total);
    $terlambat = max(0, (int) $terlambat);
    $hadir = max(0, (int) $hadir);
    $terlambat = min($terlambat, $totalAman);
    $hadir = min($hadir, $totalAman - $terlambat);
    $hadirTotal = $hadir + $terlambat;

    $persen = (int) round($hadirTotal / $totalAman * 100);
    $lingkar = 2 * M_PI * 42; // keliling lingkaran donut (r = 42)
    $busurHadir = round($lingkar * ($hadir / $totalAman), 2);
    $busurTelat = round($lingkar * ($terlambat / $totalAman), 2);
    $gradId = 'gradHadir'.\Illuminate\Support\Str::random(6);
    $gradTelatId = 'gradTelat'.\Illuminate\Support\Str::random(6);
@endphp

{{-- Donut sengaja memakai atribut SVG (width/height/transform/text), bukan
     kelas Tailwind, supaya tetap tampil walau aset belum di-build ulang. --}}
<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="flex flex-col items-center gap-5 p-5 sm:flex-row">
        <svg viewBox="0 0 120 120" width="160" height="160" class="shrink-0" role="img"
             aria-label="{{ $title }}: {{ $hadir }} hadir dari {{ $total }} {{ $satuan }}">
            <defs>
                <linearGradient id="{{ $gradId }}" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#059669" />
                    <stop offset="100%" stop-color="#0d9488" />
                </linearGradient>
                <linearGradient id="{{ $gradTelatId }}" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#f59e0b" />
                    <stop offset="100%" stop-color="#d97706" />
                </linearGradient>
            </defs>
            <g transform="rotate(-90 60 60)">
                {{-- Cincin latar = porsi "tidak hadir". --}}
                <circle cx="60" cy="60" r="42" fill="none" stroke="#e2e8f0" stroke-width="14" />

                {{-- Terlambat: mulai tepat setelah busur hadir tepat waktu. --}}
                @if ($terlambat > 0)
                    <circle cx="60" cy="60" r="42" fill="none" stroke="url(#{{ $gradTelatId }})" stroke-width="14"
                            stroke-dasharray="{{ $busurTelat }} {{ $lingkar }}"
                            stroke-dashoffset="{{ -$busurHadir }}" />
                @endif

                {{-- Tepat waktu: mulai dari atas. --}}
                @if ($hadir > 0)
                    <circle cx="60" cy="60" r="42" fill="none" stroke="url(#{{ $gradId }})" stroke-width="14"
                            stroke-dasharray="{{ $busurHadir }} {{ $lingkar }}" stroke-dashoffset="0" />
                @endif
            </g>
            <text x="60" y="54" text-anchor="middle" font-size="26" font-weight="600" fill="#0f172a">{{ $persen }}%</text>
            <text x="60" y="70" text-anchor="middle" font-size="9" fill="#64748b">kehadiran</text>
        </svg>

        <div class="min-w-0 flex-1">
            <h3 class="section-title">{{ $title }}</h3>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
            @endif

            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <dt class="text-slate-600">Hadir</dt>
                    <dd class="num ml-auto font-semibold text-slate-900">{{ $hadir }}</dd>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    <dt class="text-slate-600">Terlambat</dt>
                    <dd class="num ml-auto font-semibold text-slate-900">{{ $terlambat }}</dd>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                    <dt class="text-slate-600">{{ $tidakLabel }}</dt>
                    <dd class="num ml-auto font-semibold text-slate-900">{{ $tidak }}</dd>
                </div>
                <div class="flex items-center gap-2 border-t border-slate-100 pt-2">
                    <dt class="text-slate-500">{{ $totalLabel }}</dt>
                    <dd class="num ml-auto font-semibold text-slate-900">{{ $total }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
