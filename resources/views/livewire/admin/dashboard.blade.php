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
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Sistem online
            </span>

            <a href="{{ route('admin.laporan') }}" class="btn btn-secondary">
                <x-icon name="report" class="h-4 w-4" />
                Laporan presensi
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
<x-card title="Total Siswa" icon-tone="brand">
    <x-slot name="icon"><x-icon name="users" class="h-5 w-5" /></x-slot>
    <p class="text-3xl font-semibold tracking-tight">{{ $stats['total_siswa'] }}</p>
    <p class="mt-1 text-xs text-slate-500">Siswa aktif terdaftar</p>
</x-card>

<x-card title="Guru &amp; Tendik" icon-tone="success">
    <x-slot name="icon"><x-icon name="clipboard" class="h-5 w-5" /></x-slot>
    <p class="text-3xl font-semibold tracking-tight">{{ $stats['total_guru'] }}</p>
    <p class="mt-1 text-xs text-slate-500">Tenaga pendidik</p>
</x-card>

<x-card title="Total Pengguna" icon-tone="neutral">
    <x-slot name="icon"><x-icon name="user" class="h-5 w-5" /></x-slot>
    <p class="text-3xl font-semibold tracking-tight">{{ $stats['total_users'] }}</p>
    <p class="mt-1 text-xs text-slate-500">Seluruh akun</p>
</x-card>

<x-card title="Hadir Hari Ini" icon-tone="warning">
    <x-slot name="icon"><x-icon name="check" class="h-5 w-5" /></x-slot>
    <p class="text-3xl font-semibold tracking-tight">{{ $stats['absensi_hari_ini'] }}</p>
    <p class="mt-1 text-xs text-slate-500">Presensi tercatat</p>
</x-card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="section-title">Akses Cepat</h3>
                </div>

                <div class="card-body space-y-2">
                    @foreach ([
                        ['route' => 'admin.users', 'label' => 'Kelola Pengguna', 'icon' => 'users'],
                        ['route' => 'admin.kelas', 'label' => 'Management Kelas', 'icon' => 'classroom'],
                        ['route' => 'admin.ekstrakulikuler', 'label' => 'Ekstrakulikuler', 'icon' => 'club'],
                        ['route' => 'admin.devices', 'label' => 'Manajemen Mesin', 'icon' => 'device'],
                        ['route' => 'admin.absensi', 'label' => 'Log Absensi', 'icon' => 'clipboard'],
                        ['route' => 'admin.info', 'label' => 'Informasi Sekolah', 'icon' => 'school'],
                    ] as $item)
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-brand-700">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition group-hover:text-brand-600">
                                <x-icon :name="$item['icon']" class="h-4 w-4" />
                            </span>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="card bg-brand-600 p-5 text-white">
                <h3 class="text-sm font-semibold">Bantuan Teknis</h3>
                <p class="mt-1 text-sm text-brand-100">
                    Sinkronisasi mesin ZKTeco bermasalah atau log absensi tidak masuk?
                </p>
                <p class="mt-3 text-xs text-brand-100">
                    Hubungi administrator sekolah atau IT sekolah dengan menyertakan nomor seri mesin.
                </p>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="card flex h-full flex-col">
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
                                <th>Device SN</th>
                                <th>ID Pegawai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestLogs as $log)
                                <tr wire:key="log-{{ $log->id }}">
                                    <td class="whitespace-nowrap">
                                        <span class="block font-medium text-slate-800">
                                            {{ \Carbon\Carbon::parse($log->timestamp)->format('H:i') }} WIB
                                        </span>
                                        <span class="block text-xs text-slate-400">
                                            {{ \Carbon\Carbon::parse($log->timestamp)->translatedFormat('d M Y') }}
                                        </span>
                                    </td>
                                    <td class="font-mono text-xs">{{ $log->sn }}</td>
                                    <td class="font-mono text-xs font-medium text-brand-700">{{ $log->employee_id }}</td>
                                    <td><span class="badge badge-success">Terverifikasi</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-sm text-slate-500">
                                        Belum ada aktivitas absensi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>