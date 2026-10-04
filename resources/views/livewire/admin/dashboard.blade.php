<div class="space-y-8 animate-in fade-in duration-700">
    <!-- Welcome Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Dashboard Overview</h2>
            <p class="text-slate-500 font-medium">Selamat datang kembali, {{ auth()->user()->name }}. Inilah ringkasan aktivitas sekolah hari ini.</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="px-4 py-2 bg-white rounded-2xl shadow-sm border border-slate-200 text-sm font-bold text-slate-700 flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                System Online
            </div>
            <button class="px-5 py-2.5 bg-indigo-600 text-white rounded-2xl shadow-lg shadow-indigo-600/20 font-bold text-sm hover:bg-indigo-700 transition transform hover:-translate-y-0.5">
                Download Report
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 group hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <span class="text-green-500 text-xs font-bold">+2 New</span>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Siswa</p>
            <p class="text-3xl font-black text-slate-900">{{ $stats['total_siswa'] }}</p>
        </div>

        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 group hover:shadow-xl hover:shadow-green-500/10 transition-all duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-green-50 text-green-600 rounded-2xl group-hover:bg-green-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <span class="text-slate-400 text-xs font-bold">Updated</span>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Guru & Tendik</p>
            <p class="text-3xl font-black text-slate-900">{{ $stats['total_guru'] }}</p>
        </div>

        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 group hover:shadow-xl hover:shadow-purple-500/10 transition-all duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-slate-400 text-xs font-bold">Portal</span>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Users</p>
            <p class="text-3xl font-black text-slate-900">{{ $stats['total_users'] }}</p>
        </div>

        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 group hover:shadow-xl hover:shadow-orange-500/10 transition-all duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-orange-50 text-orange-600 rounded-2xl group-hover:bg-orange-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-orange-500 text-xs font-bold">Tap-in Now</span>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Hadir Hari Ini</p>
            <p class="text-3xl font-black text-slate-900">{{ $stats['absensi_hari_ini'] }}</p>
        </div>
    </div>

    <!-- Main Section Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Quick Actions -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                    Akses Cepat
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.users') }}" class="flex items-center p-4 bg-slate-50 rounded-2xl border border-transparent hover:border-indigo-200 hover:bg-indigo-50 transition-all duration-200 group">
                        <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center mr-4 group-hover:text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        </div>
                        <span class="font-bold text-slate-700 group-hover:text-indigo-900 transition-colors">Kelola Pengguna</span>
                    </a>
                    <a href="{{ route('admin.devices') }}" class="flex items-center p-4 bg-slate-50 rounded-2xl border border-transparent hover:border-indigo-200 hover:bg-indigo-50 transition-all duration-200 group">
                        <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center mr-4 group-hover:text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                        </div>
                        <span class="font-bold text-slate-700 group-hover:text-indigo-900 transition-colors">Manajemen Mesin</span>
                    </a>
                    <a href="{{ route('admin.absensi') }}" class="flex items-center p-4 bg-slate-50 rounded-2xl border border-transparent hover:border-indigo-200 hover:bg-indigo-50 transition-all duration-200 group">
                        <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center mr-4 group-hover:text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <span class="font-bold text-slate-700 group-hover:text-indigo-900 transition-colors">Log Absensi</span>
                    </a>
                    <a href="{{ route('admin.info') }}" class="flex items-center p-4 bg-slate-50 rounded-2xl border border-transparent hover:border-indigo-200 hover:bg-indigo-50 transition-all duration-200 group">
                        <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center mr-4 group-hover:text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.438 13.612L4.583 16m.933-2.688l3.57-1.026M18 13a3 3 0 01-3 3H9.75"></path></svg>
                        </div>
                        <span class="font-bold text-slate-700 group-hover:text-indigo-900 transition-colors">Informasi Sekolah</span>
                    </a>
                </div>
            </div>

            <div class="bg-indigo-600 p-8 rounded-[2.5rem] shadow-xl shadow-indigo-600/30 text-white relative overflow-hidden">
                <svg class="absolute -right-12 -top-12 w-48 h-48 text-indigo-500 opacity-20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5zm0 0l9-5-9-5-9 5 9 5zm0 2.81l6.75-3.75 1.5 1.5L12 20.25 3.75 14.56l1.5-1.5 6.75 3.75z"></path></svg>
                <h4 class="text-xl font-black mb-2 relative z-10">Bantuan Teknis</h4>
                <p class="text-indigo-100 text-sm mb-4 relative z-10 font-medium">Mengalami kendala sinkronisasi mesin ZKTeco?</p>
                <button class="px-6 py-2 bg-white text-indigo-600 rounded-xl font-bold text-sm hover:bg-indigo-50 transition relative z-10">
                    Hubungi IT Support
                </button>
            </div>
        </div>

        <!-- Latest Activities -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden h-full">
                <div class="p-8 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-xl font-black text-slate-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Aktifitas Absensi Terkini
                    </h3>
                    <a href="{{ route('admin.absensi') }}" class="text-xs font-bold text-indigo-600 hover:underline tracking-widest uppercase">Lihat Semua</a>
                </div>
                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Waktu Presensi</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Device SN</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Employee ID</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($latestLogs as $log)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-8 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-700">{{ \Carbon\Carbon::parse($log->timestamp)->format('H:i') }} WIB</span>
                                        <span class="text-[10px] text-slate-400 font-medium">{{ \Carbon\Carbon::parse($log->timestamp)->format('d M Y') }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-4 text-xs font-medium text-slate-500 font-mono">{{ $log->sn }}</td>
                                <td class="px-8 py-4 text-sm font-black text-indigo-600 font-mono">{{ $log->employee_id }}</td>
                                <td class="px-8 py-4">
                                    <span class="px-3 py-1 bg-green-50 text-green-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-green-100">
                                        Verified
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
