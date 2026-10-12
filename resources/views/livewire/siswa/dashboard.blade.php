<div class="space-y-6">
    <div>
        <h1 class="page-title">Halo, {{ auth()->user()->name }}</h1>
        <p class="page-subtitle">Ringkasan kehadiran dan informasi kelas kamu.</p>
    </div>

    @if ($notifikasiAbsen)
        <div @class([
            'flex items-start gap-3 rounded-lg border p-4 text-sm',
            'border-emerald-200 bg-emerald-50 text-emerald-800' => $notifikasiAbsen['tipe'] === 'masuk',
            'border-brand-200 bg-brand-50 text-brand-800' => $notifikasiAbsen['tipe'] === 'pulang',
        ])>
            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />
            <div>
                <p class="font-medium">{{ $notifikasiAbsen['pesan'] }}</p>
                <p class="mt-0.5 text-xs opacity-80">
                    Waktu {{ $notifikasiAbsen['tipe'] === 'masuk' ? 'Masuk' : 'Pulang' }}: {{ \Carbon\Carbon::parse($notifikasiAbsen['waktu'])->format('H:i') }} WIB
                </p>
            </div>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('siswa.absensi') }}" class="btn btn-brand">Lihat Detail Absensi</a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-card title="Kelas">
            <x-slot name="icon"><x-icon name="classroom" class="h-5 w-5" /></x-slot>
            <p class="text-2xl font-semibold tracking-tight">{{ $kelas?->nama_kelas ?? 'Belum diatur' }}</p>
        </x-card>

        <x-card title="NIS">
            <x-slot name="icon"><x-icon name="user" class="h-5 w-5" /></x-slot>
            <p class="text-2xl font-semibold tracking-tight">{{ $siswa?->nis ?? '-' }}</p>
        </x-card>

        <x-card title="Kehadiran Tercatat" icon-tone="success">
            <x-slot name="icon"><x-icon name="check" class="h-5 w-5" /></x-slot>
            <p class="text-2xl font-semibold tracking-tight">{{ $hadir }}</p>
            <p class="mt-1 text-xs text-slate-500">dari {{ $total }} log presensi</p>
        </x-card>

        <x-card title="Absen Hari Ini">
            <x-slot name="icon"><x-icon name="clock" class="h-5 w-5" /></x-slot>
            <div class="space-y-1">
                <p class="text-sm">Masuk: <span class="font-medium">{{ $absenHariIni && $absenHariIni['masuk'] ? \Carbon\Carbon::parse($absenHariIni['masuk'])->format('H:i') . ' WIB' : '-' }}</span></p>
                <p class="text-sm">Pulang: <span class="font-medium">{{ $absenHariIni && $absenHariIni['pulang'] ? \Carbon\Carbon::parse($absenHariIni['pulang'])->format('H:i') . ' WIB' : '-' }}</span></p>
            </div>
        </x-card>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Presensi Terakhir</h2>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Jenis</th><th>Nama</th><th>Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($terakhir as $log)
                        <tr wire:key="hadir-{{ $log->id }}">
                            <td class="whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($log->timestamp)->translatedFormat('d M Y H:i') }} WIB
                            </td>
                            <td>
                                <span class="badge {{ $log->jenis === 'masuk' ? 'badge-success' : 'badge-brand' }}">
                                    {{ ucfirst($log->jenis ?? 'Hadir') }}
                                </span>
                            </td>
                            <td class="text-sm">{{ auth()->user()->name ?? ($siswa?->nama ?? '-') }}</td>
                            <td class="text-sm">{{ $kelas?->nama_kelas ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                                Belum ada data presensi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Ekstrakulikuler yang Diikuti</h2>
            <span class="badge badge-neutral">{{ $ekstrakulikuler->count() }} kegiatan</span>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Ekstrakulikuler</th>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Lokasi</th>
                        <th>Tahun Ajaran / Semester</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ekstrakulikuler as $eks)
                        <tr wire:key="eks-{{ $eks->id }}">
                            <td>
                                <div class="font-medium text-slate-700">{{ $eks->nama }}</div>
                                @if ($eks->deskripsi)
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $eks->deskripsi }}</div>
                                @endif
                            </td>
                            <td class="text-sm">{{ $eks->hari ?? '-' }}</td>
                            <td class="text-sm">
                                @if ($eks->jam_mulai || $eks->jam_selesai)
                                    {{ $eks->jam_mulai }}{{ $eks->jam_selesai ? ' - ' . $eks->jam_selesai : '' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-sm">{{ $eks->lokasi ?? '-' }}</td>
                            <td class="text-sm">{{ $eks->tahun_ajaran ?? '-' }}{{ $eks->semester ? ' / ' . $eks->semester : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                                Belum mengikuti ekstrakulikuler.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>