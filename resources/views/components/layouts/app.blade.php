<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SIAKAD' }} &middot; SDN Cikampek Selatan 2</title>
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
            ['route' => 'admin.izin', 'label' => 'Catat Izin', 'icon' => 'document'],
            ['route' => 'admin.konfirmasi', 'label' => 'Konfirmasi Izin', 'icon' => 'check'],
            ['route' => 'admin.devices', 'label' => 'Mesin Absensi', 'icon' => 'device'],
            ['route' => 'admin.laporan', 'label' => 'Laporan Presensi', 'icon' => 'report'],
        ],
        'guru' => [
            ['route' => 'guru.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'guru.absensi', 'label' => 'Absensi Kelas', 'icon' => 'clipboard'],
            ['route' => 'guru.nilai', 'label' => 'Input Nilai', 'icon' => 'grade'],
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
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-slate-900
                  transition-transform duration-200 ease-out lg:static lg:translate-x-0 lg:shadow-none
                  shadow-2xl shadow-slate-900/20"
           :class="sidebarOpen && '!translate-x-0'"
           aria-label="Menu utama">

        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/5 px-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-icon name="book" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold tracking-tight text-white">SIAKAD</p>
                <p class="truncate text-[11px] text-slate-400">SDN Cikampek Selatan 2</p>
            </div>
            <button type="button" @click="sidebarOpen = false"
                    class="-mr-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="Tutup menu">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>

        @if ($menu)
            <nav class="sidebar-scroll flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Menu</p>

                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                           'bg-brand-600 text-white' => request()->routeIs($item['route']),
                           'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs($item['route']),
                       ])
                       @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif

        <div class="shrink-0 border-t border-white/5 p-3">
            <div class="mb-2 flex items-center gap-3 rounded-lg px-2 py-2">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600/15 text-xs font-semibold text-brand-300">
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
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-400 transition hover:bg-white/5 hover:text-white">
                    <x-icon name="logout" class="h-5 w-5 shrink-0" />
                    Keluar
                </button>
            </form>
        </div>
    </aside>
@endauth

<div class="flex min-h-0 min-w-0 flex-1 flex-col">
    <header class="flex h-16 shrink-0 items-center gap-4 border-b border-slate-200 bg-white px-4 lg:px-8">
        <button type="button" @click="sidebarOpen = true"
                class="-ml-1 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden"
                aria-label="Buka menu">
            <x-icon name="menu" class="h-5 w-5" />
        </button>

        <h1 class="min-w-0 flex-1 truncate text-base font-semibold tracking-tight text-slate-800">
            {{ $title ?? 'SDN Cikampek Selatan 2' }}
        </h1>

        <div class="flex shrink-0 items-center gap-2">
            @auth
                <livewire:shared.notifikasi-bell />
            @endauth
        </div>
    </header>

    <div class="flex-1 overflow-y-auto">
        {{ $slot }}
    </div>

    <footer class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} SDN Cikampek Selatan 2 &middot; Sistem Informasi Akademik &amp; Absensi Digital
    </footer>
</div>

</div>

@livewireScripts
</body>
</html>