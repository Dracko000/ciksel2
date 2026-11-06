<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <img src="/logo-sekolah.png" alt="Logo {{ config('app.school_name') }}"
                 class="h-14 w-14 shrink-0 rounded-xl object-contain">

            <div>
                <h2 class="page-title">Ringkasan Sekolah</h2>
                <p class="page-subtitle">
                    Selamat datang kembali, {{ auth()->user()->name }}. Berikut ringkasan aktivitas hari ini.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-success">
                <span class="h-1.5 w-1.5 rounded-full bg-sukses-600"></span>
                Sistem online
            </span>

            <a href="{{ route('admin.laporan') }}" class="btn btn-primary">
                <x-icon name="report" class="h-4 w-4" />
                Laporan presensi
            </a>
        </div>
    </div>

    @php
        // Donut kehadiran: siswa unik yang tap masuk hari ini vs belum/tidak
        // hadir. Sumbernya PieData['all'] yang juga dipakai grafik per kelas.
        $kehadiran = $pieData['all'];
    @endphp

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {{-- Kehadiran hari ini divisualkan sebagai donut: satu pandangan untuk
             proporsi hadir vs tidak hadir. --}}
        <x-donut-kehadiran
            :hadir="$kehadiran['hadir']"
            :terlambat="$kehadiran['terlambat']"
            :tidak="$kehadiran['tidak']"
            :total="$kehadiran['total']"
            title="Kehadiran Hari Ini"
            subtitle="Siswa hadir berdasarkan tap masuk hari ini."
            total-label="Total Siswa"
            satuan="siswa" />

        {{-- Kehadiran guru & tendik, berdampingan dengan donut siswa. --}}
        <x-donut-kehadiran
            :hadir="$kehadiranGuru['hadir']"
            :terlambat="$kehadiranGuru['terlambat']"
            :tidak="$kehadiranGuru['tidak']"
            :total="$kehadiranGuru['total']"
            title="Kehadiran Guru & Tendik Hari Ini"
            subtitle="Guru & tendik yang tap masuk hari ini."
            total-label="Total Guru & Tendik"
            satuan="guru" />
    </div>

    
    <div class="card mt-6">
        <div class="card-header">
            <h3 class="section-title">Aktivitas Absensi Terkini</h3>
            <a href="{{ route('admin.absensi') }}" class="text-xs font-medium text-brand-600 transition hover:text-brand-700">
                Lihat semua
            </a>
        </div>
        <div class="table-scroll flex-1">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>NIS/NIK/NIP</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestLogs as $log)
                        <tr wire:key="log-{{ $log->id }}">
                            <td class="whitespace-nowrap">
                                <span class="block font-medium text-slate-900">
                                    {{ \Carbon\Carbon::parse($log->timestamp)->format('H:i') }} WIB
                                </span>
                                <span class="block text-xs text-slate-400">
                                    {{ \Carbon\Carbon::parse($log->timestamp)->translatedFormat('d M Y') }}
                                </span>
                            </td>
                            @php
                                $user = null;
                                $kelasName = '-';
                                $nid = '-';
                                $siswa = null;
                                $guru = null;
                                if ($log->employee_id) {
                                    $siswa = App\Models\Siswa::where('wajah_id_zkteco', $log->employee_id)->with('user', 'kelas')->first();
                                    if ($siswa) {
                                        $user = $siswa->user;
                                        $kelasName = $siswa->kelas?->nama_kelas ?? '-';
                                        $nid = $siswa->nis ?? '-';
                                    } else {
                                        $guru = App\Models\Guru::where('wajah_id_zkteco', $log->employee_id)->with('user')->first();
                                        if ($guru) {
                                            $user = $guru->user;
                                            $nid = $guru->nip ?? '-';
                                        }
                                    }
                                }
                            @endphp
                            <td class="text-xs text-slate-700">{{ $user?->name ?? ($siswa?->nama ?? $guru?->nama ?? '-') }}</td>
                            <td class="text-xs text-slate-700">{{ $kelasName }}</td>
                            <td class="num text-xs font-medium text-slate-500">{{ $nid }}</td>
                            <td><span class="badge badge-success">Terverifikasi</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <x-icon name="clipboard" class="h-10 w-10 text-slate-400" />
                                    <p class="empty-state-title">Belum ada aktivitas absensi</p>
                                    <p class="empty-state-text">
                                        Log absensi akan muncul di sini setelah mesin ZKTeco mengirim
                                        data pada hari ini.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
