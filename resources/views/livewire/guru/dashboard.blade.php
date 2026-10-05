<div class="space-y-6">
    <div>
        <h2 class="page-title">Dashboard Guru</h2>
        <p class="page-subtitle">
            Selamat datang, {{ auth()->user()->name }}. Berikut ringkasan aktivitas mengajar Anda.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('guru.absensi') }}"
           class="card flex flex-col gap-2 p-5 transition hover:border-info-100 hover:bg-info-50">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-info-50 text-info-700">
                <x-icon name="clipboard" class="h-5 w-5" />
            </span>
            <h3 class="text-sm font-semibold text-slate-800">Absensi Kelas</h3>
            <p class="text-sm text-slate-500">Pantau kehadiran siswa di kelas Anda hari ini.</p>
        </a>

        <a href="{{ route('guru.nilai') }}"
           class="card flex flex-col gap-2 p-5 transition hover:border-info-100 hover:bg-info-50">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-info-50 text-info-700">
                <x-icon name="grade" class="h-5 w-5" />
            </span>
            <h3 class="text-sm font-semibold text-slate-800">Input Nilai</h3>
            <p class="text-sm text-slate-500">Kelola nilai siswa untuk semester berjalan.</p>
        </a>

        <div class="card flex flex-col gap-2 bg-slate-50 p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                <x-icon name="document" class="h-5 w-5" />
            </span>
            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                Absen Manual
                <span class="badge badge-neutral">Segera</span>
            </h3>
            <p class="text-sm text-slate-400">
                Input absensi manual jika siswa tidak membawa kartu.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="section-title">Jadwal Mengajar Hari Ini</h3>
            <span class="badge badge-neutral">{{ $jadwalHariIni->count() }} sesi</span>
        </div>

        <div class="card-body">
            @if ($jadwalHariIni->isEmpty())
                <p class="py-6 text-center text-sm text-slate-500">
                    Tidak ada jadwal mengajar hari ini.
                </p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($jadwalHariIni as $jadwal)
                        <li wire:key="jadwal-{{ $jadwal->id }}" class="flex items-center gap-4 py-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <x-icon name="book" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-800">
                                    {{ $jadwal->mata_pelajaran }} &mdash; Kelas {{ $jadwal->kelas?->nama_kelas ?? '-' }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }}
                                    s/d
                                    {{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>