<div class="space-y-8 animate-in fade-in duration-700">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Portal Orang Tua</h2>
            <p class="text-slate-500 font-medium">Pantau perkembangan akademik dan kehadiran buah hati Anda.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Data Anak -->
        <div class="lg:col-span-2 space-y-6">
            <h3 class="text-xl font-black text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Informasi Siswa
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($anak as $item)
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl transition-all duration-300 group">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-xl font-black text-slate-900">{{ $item->nama }}</h4>
                            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">{{ $item->nis }} • Kelas {{ $item->kelas->nama_kelas ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <a href="{{ route('ortu.ijin') }}" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-transparent hover:border-orange-200 hover:bg-orange-50 transition group/btn">
                            <span class="text-sm font-bold text-slate-700 group-hover/btn:text-orange-900">Ajukan Ijin/Sakit</span>
                            <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                        <a href="{{ route('ortu.raport.download', $item->id) }}" class="flex items-center justify-between p-4 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 transition group/btn">
                            <span class="text-sm font-bold text-white">Download E-Raport PDF</span>
                            <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </a>
                    </div>
                </div>
                @empty
                <div class="col-span-full">
                    <x-card class="text-center py-12">
                        <p class="text-slate-400 font-medium italic">Belum ada data siswa yang tertaut.</p>
                    </x-card>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Pengumuman -->
        <div class="lg:col-span-1 space-y-6">
            <h3 class="text-xl font-black text-slate-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.438 13.612L4.583 16m.933-2.688l3.57-1.026M18 13a3 3 0 01-3 3H9.75"></path></svg>
                Berita Sekolah
            </h3>
            <div class="space-y-4">
                @foreach($informasi as $info)
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition">
                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">{{ \Carbon\Carbon::parse($info->tanggal_publikasi)->format('d M Y') }}</span>
                    <h5 class="font-black text-slate-800 mt-1 mb-2">{{ $info->judul }}</h5>
                    <p class="text-sm text-slate-500 line-clamp-3 leading-relaxed">{{ $info->konten }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
