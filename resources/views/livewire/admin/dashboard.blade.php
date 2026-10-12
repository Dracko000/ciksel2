<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Ringkasan Sekolah</h2>
            <p class="page-subtitle">
                Selamat datang kembali, {{ auth()->user()->name }}. Berikut ringkasan aktivitas hari ini.
            </p>
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

        $cards = [
            [
                'label' => 'Izin Menunggu',
                'value' => $stats['izin_menunggu'],
                'icon' => 'document',
                'delta' => $stats['izin_menunggu'] > 0 ? 'Perlu ditinjau' : 'Semua selesai',
                'deltaUp' => $stats['izin_menunggu'] > 0,
                'tone' => 'warning',
            ],
            [
                'label' => 'Mesin Online',
                'value' => $stats['mesin_online'],
                'icon' => 'device',
                'delta' => 'Mesin ZKTeco aktif',
                'tone' => 'info',
            ],
        ];

        $statIconTone = [
            'neutral' => 'bg-gradient-to-br from-slate-500 to-slate-600',
            'success' => 'bg-gradient-to-br from-emerald-600 to-teal-700',
            'warning' => 'bg-gradient-to-br from-amber-600 to-orange-700',
            'danger' => 'bg-gradient-to-br from-rose-600 to-red-700',
            'info' => 'bg-gradient-to-br from-sky-600 to-indigo-700',
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Kehadiran hari ini divisualkan sebagai donut: satu pandangan untuk
             proporsi hadir vs tidak hadir, menggantikan dua kartu angka. --}}
        <x-donut-kehadiran
            :hadir="$kehadiran['hadir']"
            :terlambat="$kehadiran['terlambat']"
            :tidak="$kehadiran['tidak']"
            :total="$kehadiran['total']"
            title="Kehadiran Hari Ini"
            subtitle="Siswa hadir berdasarkan tap masuk hari ini."
            total-label="Total Siswa"
            satuan="siswa"
            class="sm:col-span-2 lg:col-span-2" />

        @foreach ($cards as $card)
            <div class="stat-card">
                <span class="stat-icon {{ $statIconTone[$card['tone']] }}">
                    <x-icon :name="$card['icon']" class="h-5 w-5" />
                </span>

                <div class="min-w-0">
                    <p class="stat-value">{{ $card['value'] }}</p>
                    <p class="stat-label">{{ $card['label'] }}</p>
                    <p class="stat-delta {{ ($card['deltaUp'] ?? false) ? 'stat-delta-down' : 'text-slate-400' }}">
                        @if ($card['deltaUp'] ?? false)
                            <x-icon name="warning" class="h-3.5 w-3.5" />
                        @endif
                        {{ $card['delta'] }}
                    </p>
                </div>
            </div>
        @endforeach
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
