<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ADMS">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/artifacts/logo.png">
    <title>{{ $title ?? 'ADMS' }} &middot; {{ config('app.school_name') }}</title>
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
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'tone' => 'bg-sky-100 text-sky-600'],
            ['route' => 'admin.users', 'label' => 'Data Pengguna', 'icon' => 'users', 'tone' => 'bg-violet-100 text-violet-600'],
            ['route' => 'admin.kelas', 'label' => 'Management Kelas', 'icon' => 'classroom', 'tone' => 'bg-emerald-100 text-emerald-600'],
            ['route' => 'admin.ekstrakulikuler', 'label' => 'Ekstrakulikuler', 'icon' => 'club', 'tone' => 'bg-amber-100 text-amber-600'],
            ['route' => 'admin.absensi', 'label' => 'Monitor Absensi', 'icon' => 'clipboard', 'tone' => 'bg-rose-100 text-rose-600'],
            ['route' => 'admin.presensi-guru', 'label' => 'Presensi Guru', 'icon' => 'presence', 'tone' => 'bg-cyan-100 text-cyan-600'],
            ['route' => 'admin.konfirmasi', 'label' => 'Konfirmasi Izin', 'icon' => 'check', 'tone' => 'bg-teal-100 text-teal-600'],
            ['route' => 'admin.devices', 'label' => 'Mesin Absensi', 'icon' => 'device', 'tone' => 'bg-indigo-100 text-indigo-600'],
            ['route' => 'admin.laporan', 'label' => 'Laporan Presensi', 'icon' => 'report', 'tone' => 'bg-orange-100 text-orange-600'],
        ],
        'guru' => [
            ['route' => 'guru.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'tone' => 'bg-sky-100 text-sky-600'],
            ['route' => 'guru.absensi', 'label' => 'Absensi Kelas', 'icon' => 'clipboard', 'tone' => 'bg-emerald-100 text-emerald-600'],
            ['route' => 'guru.nilai', 'label' => 'Input Nilai', 'icon' => 'grade', 'tone' => 'bg-violet-100 text-violet-600'],
        ],
        'siswa' => [
            ['route' => 'siswa.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'tone' => 'bg-sky-100 text-sky-600'],
            ['route' => 'siswa.izin', 'label' => 'Pengajuan Izin', 'icon' => 'document', 'tone' => 'bg-amber-100 text-amber-600'],
        ],
        default => [],
    };
@endphp

@auth
    {{-- Lapisan gelap di luar sidebar supaya overlay tidak ikut tergeser --}}
    <div x-show="sidebarOpen" x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-[2px] lg:hidden"></div>
@endauth

<div class="flex h-full min-w-0">
@auth
    {{-- Sidebar 240px, latar putih; tiap menu punya chip ikon berwarna --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-60 -translate-x-full flex-col border-r border-slate-200
                  bg-white transition-transform duration-200 ease-out lg:static lg:translate-x-0
                  shadow-xl shadow-slate-900/10 lg:shadow-none"
           :class="sidebarOpen && '!translate-x-0'"
           aria-label="Menu utama">

        {{-- Strip pelangi: aksen warna yang menyatu dengan header --}}
        <div class="rainbow-bar h-1 shrink-0" aria-hidden="true"></div>

        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-rose-600
                         text-white shadow-sm shadow-brand-600/40">
                <x-icon name="book" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold tracking-tight text-slate-900">ADMS</p>
                <p class="truncate text-[11px] text-slate-500">{{ config('app.school_name') }}</p>
            </div>
            <button type="button" @click="sidebarOpen = false"
                    class="-mr-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden"
                    aria-label="Tutup menu">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>

        @if ($menu)
            <nav class="sidebar-scroll flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Menu</p>

                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'sidebar-link',
                           'sidebar-link-active' => request()->routeIs($item['route']),
                       ])
                       @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md {{ $item['tone'] }}">
                            <x-icon :name="$item['icon']" class="h-4 w-4" />
                        </span>
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif

        <div class="shrink-0 border-t border-slate-200 p-3">
            <div class="mb-2 flex items-center gap-3 rounded-lg px-2 py-2">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br
                             from-brand-500 to-rose-500 text-xs font-semibold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
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
    {{-- Strip pelangi di atas header: pasangan dari strip yang sama di sidebar --}}
    <div class="rainbow-bar h-1 shrink-0" aria-hidden="true"></div>

    <header class="flex h-16 shrink-0 items-center gap-4 border-b border-slate-200 bg-white px-4 lg:px-8">
        <button type="button" @click="sidebarOpen = true"
                class="-ml-1 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 lg:hidden"
                aria-label="Buka menu">
            <x-icon name="menu" class="h-5 w-5" />
        </button>

        <h1 class="min-w-0 flex-1 truncate text-base font-semibold tracking-tight text-slate-900">
            {{ $title ?? config('app.school_name') }}
        </h1>

        <div class="flex shrink-0 items-center gap-3">
            @auth
                <livewire:shared.notifikasi-bell />

                <span class="hidden h-8 w-px bg-slate-200 sm:block" aria-hidden="true"></span>

                <div class="hidden items-center gap-2.5 sm:flex">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br
                                 from-brand-500 to-rose-500 text-xs font-semibold text-white"
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
        <div class="mx-auto w-full max-w-[1400px] px-6 py-6">
            {{ $slot }}
        </div>
    </div>

    <footer class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} {{ config('app.school_name') }} &middot; Sistem Informasi Akademik &amp; Absensi Digital
    </footer>
</div>

</div>

@livewireScripts
</body>
</html>