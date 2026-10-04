<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIAKAD - SDN Cikampek Selatan 2</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-gradient { background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="h-full text-slate-900 overflow-hidden">
    <div class="flex h-full">
        @auth
        <!-- Sidebar -->
        <aside class="w-64 sidebar-gradient text-slate-300 flex-shrink-0 hidden lg:flex flex-col shadow-2xl">
            <div class="p-6 flex items-center space-x-3">
                <div class="w-10 h-10 bg-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/50">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <div>
                    <h1 class="text-white font-bold tracking-tight">SIAKAD</h1>
                    <p class="text-[10px] uppercase tracking-widest font-semibold text-slate-500">SDN CS 2</p>
                </div>
            </div>

            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto custom-scrollbar">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Main Menu</p>
                
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="text-sm font-medium">Dashboard</span>
                    </a>
                    <a href="{{ route('admin.users') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('admin.users') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span class="text-sm font-medium">Data Pengguna</span>
                    </a>
                    <a href="{{ route('admin.absensi') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('admin.absensi') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <span class="text-sm font-medium">Monitor Absensi</span>
                    </a>
                    <a href="{{ route('admin.devices') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('admin.devices') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                        <span class="text-sm font-medium">Manajemen Mesin</span>
                    </a>
                    <a href="{{ route('admin.laporan') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('admin.laporan') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="text-sm font-medium">Laporan Presensi</span>
                    </a>
                @endif

                @if(auth()->user()->role === 'guru')
                    <a href="{{ route('guru.dashboard') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('guru.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="text-sm font-medium">Dashboard Guru</span>
                    </a>
                    <a href="{{ route('guru.absensi') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('guru.absensi') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="text-sm font-medium">Absensi Kelas</span>
                    </a>
                    <a href="{{ route('guru.nilai') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('guru.nilai') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span class="text-sm font-medium">Input Nilai</span>
                    </a>
                @endif

                @if(auth()->user()->role === 'ortu')
                    <a href="{{ route('ortu.dashboard') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('ortu.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="text-sm font-medium">Portal Ortu</span>
                    </a>
                    <a href="{{ route('ortu.ijin') }}" class="flex items-center px-3 py-2.5 rounded-xl hover:bg-slate-700/50 hover:text-white transition group {{ request()->routeIs('ortu.ijin') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="text-sm font-medium">Ajukan Ijin</span>
                    </a>
                @endif

                <div class="pt-4 mt-4 border-t border-slate-700/50">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-red-500/10 hover:text-red-400 transition">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span class="text-sm font-medium">Keluar</span>
                        </button>
                    </form>
                </div>
            </nav>

            <div class="p-4">
                <div class="bg-slate-700/30 rounded-2xl p-4 border border-slate-700/50">
                    <p class="text-xs text-slate-500 mb-1">Logged in as:</p>
                    <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest mt-1">{{ auth()->user()->role }}</p>
                </div>
            </div>
        </aside>
        @endauth

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden relative">
            @auth
            <!-- Top Header -->
            <header class="h-16 glass-effect border-b border-slate-200/60 flex items-center justify-between px-8 z-30">
                <div class="lg:hidden">
                    <button class="p-2 text-slate-500 hover:bg-slate-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
                <div class="flex items-center">
                    <h2 class="text-lg font-bold text-slate-800 tracking-tight">SDN Cikampek Selatan 2</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 text-slate-400 hover:text-indigo-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </button>
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs ring-2 ring-indigo-50 border border-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                </div>
            </header>
            @endauth

            <div class="flex-1 overflow-y-auto p-4 lg:p-8 custom-scrollbar">
                {{ $slot }}
            </div>

            <!-- Footer -->
            <footer class="h-10 text-center text-[10px] text-slate-400 flex items-center justify-center border-t border-slate-100 bg-white">
                &copy; 2026 SDN Cikampek Selatan 2 • Professional School Management System
            </footer>
        </main>
    </div>

    @livewireScripts
</body>
</html>
