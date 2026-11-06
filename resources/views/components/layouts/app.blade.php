<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1220">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.school_name') }}">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/logo-sekolah.png">
    <title>{{ $title ?? config('app.school_name') }}@isset($title) &middot; {{ config('app.school_name') }}@endisset</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-canvas text-slate-900" x-data="{ sidebarOpen: false }">
@php
    $role = auth()->user()?->role;

    $menu = match ($role) {
        'admin' => [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'admin.users', 'label' => 'Data Pengguna', 'icon' => 'users'],
            ['route' => 'admin.kelas', 'label' => 'Management Kelas', 'icon' => 'classroom'],
            ['route' => 'admin.ekstrakulikuler', 'label' => 'Ekstrakulikuler', 'icon' => 'club'],
            ['route' => 'admin.absensi', 'label' => 'Monitor Absensi', 'icon' => 'clipboard'],
            [
                'label' => 'Presensi', 'icon' => 'presence',
                'children' => [
                    ['route' => 'admin.presensi-guru', 'label' => 'Presensi Guru'],
                    ['route' => 'admin.presensi-murid', 'label' => 'Presensi Murid'],
                ],
            ],
            ['route' => 'admin.rfid', 'label' => 'RFID Belum Terdaftar', 'icon' => 'warning'],
            [
                'label' => 'Atur Jam', 'icon' => 'clock',
                'children' => [
                    ['route' => 'admin.jam-guru', 'label' => 'Jam Guru'],
                    ['route' => 'admin.jam-kelas', 'label' => 'Jam Kelas'],
                ],
            ],
            ['route' => 'admin.konfirmasi', 'label' => 'Konfirmasi Izin', 'icon' => 'check', 'badge' => true],
            ['route' => 'admin.devices', 'label' => 'Mesin Absensi', 'icon' => 'device'],
            ['route' => 'admin.laporan', 'label' => 'Laporan Presensi', 'icon' => 'report'],
        ],
        'guru' => [
            ['route' => 'guru.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'guru.absensi', 'label' => 'Absensi Kelas', 'icon' => 'clipboard'],
            ['route' => 'guru.nilai', 'label' => 'Input Nilai', 'icon' => 'grade'],
        ],
        'siswa' => [
            ['route' => 'siswa.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'siswa.izin', 'label' => 'Pengajuan Izin', 'icon' => 'document'],
        ],
        default => [],
    };
@endphp

@auth
    {{-- Lapisan gelap di luar sidebar supaya overlay tidak ikut tergeser --}}
    <div x-show="sidebarOpen" x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"></div>
@endauth

<div class="flex h-full min-w-0">
@auth
    {{-- Sidebar navy gelap, 256px. Menu aktif ditandai bar merah tipis. --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col
                  bg-navy-900 transition-transform duration-200 ease-out lg:static lg:translate-x-0
                  shadow-2xl shadow-slate-950/40 lg:shadow-none"
           :class="sidebarOpen && '!translate-x-0'"
           aria-label="Menu utama">

        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
            <span class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg bg-white">
                <img src="/logo-sekolah.png" alt="Logo {{ config('app.school_name') }}" class="h-9 w-9 object-contain">
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-[13px] font-semibold leading-tight tracking-tight text-white">
                    {{ config('app.school_name') }}
                </p>
                <p class="text-[11px] text-slate-500">Sistem Absensi Digital</p>
            </div>
            <button type="button" @click="sidebarOpen = false"
                    class="-mr-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="Tutup menu">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>

        @if ($menu)
            <nav class="sidebar-scroll flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Menu</p>

                @foreach ($menu as $item)
                    @if (! empty($item['children']))
                        @php
                            $anakAktif = collect($item['children'])->contains(fn ($anak) => request()->routeIs($anak['route']));
                        @endphp
                        <div wire:key="menu-parent-{{ $loop->index }}" x-data="{ open: {{ $anakAktif ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open"
                                    @class([
                                        'sidebar-link w-full',
                                        'sidebar-link-active' => $anakAktif,
                                    ])>
                                <span class="sidebar-chip">
                                    <x-icon :name="$item['icon']" class="h-4 w-4" />
                                </span>
                                <span class="flex-1 truncate text-left">{{ $item['label'] }}</span>
                                <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200"
                                        x-bind:class="{ 'rotate-180': open }" />
                            </button>

                            <div x-show="open" x-transition.opacity class="ml-5 mt-1 space-y-1 border-l border-white/10 pl-5">
                                @foreach ($item['children'] as $anak)
                                    <a href="{{ route($anak['route']) }}"
                                       @class([
                                           'sidebar-link',
                                           'sidebar-link-active' => request()->routeIs($anak['route']),
                                       ])
                                       @if (request()->routeIs($anak['route'])) aria-current="page" @endif>
                                        <span class="truncate">{{ $anak['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'sidebar-link',
                               'sidebar-link-active' => request()->routeIs($item['route']),
                           ])
                           @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                            <span class="sidebar-chip">
                                <x-icon :name="$item['icon']" class="h-4 w-4" />
                            </span>
                            <span class="truncate">{{ $item['label'] }}</span>
                            @if (! empty($item['badge']))
                                <livewire:admin.izin-pending-badge />
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>
        @endif

        <div class="shrink-0 border-t border-white/10 p-3">
            <div class="mb-2 flex items-center gap-3 rounded-lg px-2 py-2">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br
                             from-brand-500 to-brand-700 text-xs font-semibold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ auth()->user()->role }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="sidebar-link w-full">
                    <x-icon name="logout" class="h-5 w-5 shrink-0" />
                    Keluar
                </button>
            </form>
        </div>
    </aside>
@endauth

<div class="flex min-h-0 min-w-0 flex-1 flex-col">
    <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-4 border-b border-slate-200/80
                   bg-white/90 px-4 backdrop-blur-md lg:px-8">
        <button type="button" @click="sidebarOpen = true"
                class="-ml-1 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 lg:hidden"
                aria-label="Buka menu">
            <x-icon name="menu" class="h-5 w-5" />
        </button>

        <h1 class="min-w-0 flex-1 truncate text-[15px] font-semibold tracking-tight text-slate-900">
            {{ $title ?? config('app.school_name') }}
        </h1>

        <div class="flex shrink-0 items-center gap-3">
            @auth
                <livewire:shared.notifikasi-bell />

                <span class="hidden h-8 w-px bg-slate-200 sm:block" aria-hidden="true"></span>

                <div class="hidden items-center gap-2.5 sm:flex">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br
                                 from-brand-500 to-brand-700 text-xs font-semibold text-white"
                          aria-hidden="true">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </span>
                    <span class="max-w-[160px] truncate text-sm font-medium text-slate-700">
                        {{ auth()->user()->name }}
                    </span>
                </div>
            @endauth
        </div>
    </header>

    <div class="flex-1 overflow-y-auto">
        <div class="mx-auto w-full max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </div>

    <footer class="shrink-0 border-t border-slate-200/80 px-4 py-3 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} {{ config('app.school_name') }} &middot; Sistem Informasi Akademik &amp; Absensi Digital
    </footer>
</div>

</div>

@livewireScripts
</body>
</html>
