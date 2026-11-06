<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="page-title">Presensi Murid</h2>
            <p class="page-subtitle">
                Rekap kehadiran siswa dari tap mesin ZKTeco pada tanggal dan kelas yang dipilih.
            </p>
        </div>

        <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-3 lg:w-auto lg:min-w-[36rem]">
            <div>
                <label for="kelasFilter" class="label">Kelas</label>
                <select id="kelasFilter" wire:model.live="kelasFilter" class="input">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelasList as $kelas)
                        <option wire:key="kelas-{{ $kelas->id }}" value="{{ $kelas->id }}">
                            {{ $kelas->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="search" class="label">Cari Siswa</label>
                <input id="search" type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Nama atau NIS" class="input">
            </div>

            <div>
                <label for="dateFilter" class="label">Pilih Tanggal</label>
                <input id="dateFilter" type="date" wire:model.live="dateFilter" class="input">
            </div>
        </div>
    </div>

    <x-donut-kehadiran
        :hadir="$hadirTepatTanggal"
        :terlambat="$terlambatTanggal"
        :tidak="$alphaTanggal"
        :total="$hadirTanggal + $alphaTanggal"
        title="Kehadiran Murid"
        subtitle="Siswa yang tap masuk pada tanggal ini."
        total-label="Total Murid"
        satuan="siswa"
        class="lg:max-w-md" />

    <div class="table-shell">
        <div class="card-header">
            <div>
                <h3 class="section-title">Rekap Harian Murid</h3>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($search !== '')
                    <span class="badge badge-neutral">Cari: {{ $search }}</span>
                @endif
                <span class="badge badge-success">
                    <span class="h-1.5 w-1.5 rounded-full bg-sukses-600"></span>
                    {{ $hadir }} hadir
                </span>
                <span class="badge badge-warning">{{ $terlambat }} terlambat</span>
                <span class="badge badge-danger">{{ $alpha }} alpha</span>
                <span class="badge badge-neutral">{{ $presensi->total() }} data</span>

                <span class="mx-1 hidden h-6 w-px bg-slate-200 sm:block"></span>

                <div class="flex flex-wrap items-center gap-1.5">
                    <a class="btn btn-sm btn-secondary"
                       href="{{ route('admin.absensi.download', ['periode' => 'harian', 'tanggal' => $dateFilter, 'peran' => 'siswa', 'kelas_id' => $kelasFilter ?: null]) }}">
                        <x-icon name="download" class="h-3.5 w-3.5" />
                        Harian
                    </a>
                    <a class="btn btn-sm btn-secondary"
                       href="{{ route('admin.absensi.download', ['periode' => 'mingguan', 'tanggal' => $dateFilter, 'peran' => 'siswa', 'kelas_id' => $kelasFilter ?: null]) }}">
                        <x-icon name="download" class="h-3.5 w-3.5" />
                        Mingguan
                    </a>
                    <a class="btn btn-sm btn-secondary"
                       href="{{ route('admin.absensi.download', ['periode' => 'bulanan', 'tanggal' => $dateFilter, 'peran' => 'siswa', 'kelas_id' => $kelasFilter ?: null]) }}">
                        <x-icon name="download" class="h-3.5 w-3.5" />
                        Bulanan
                    </a>
                </div>
            </div>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-14">No</th>
                        <th>Nama</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>ID Mesin</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Jumlah Masuk</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $nomor = $presensi->firstItem() ?? 0; @endphp

                    @forelse ($presensi as $baris)
                        @php $nomor++; @endphp
                        <tr wire:key="presensi-murid-{{ $baris['employee_id'] }}">
                            <td class="num text-xs text-slate-400">{{ $nomor }}</td>
                            <td class="text-xs font-medium text-slate-800">{{ $baris['nama'] }}</td>
                            <td class="num text-xs text-slate-500">{{ $baris['nis'] }}</td>
                            <td class="text-xs text-slate-500">{{ $baris['kelas'] ?? '-' }}</td>
                            <td class="num text-xs text-slate-500">{{ $baris['employee_id'] }}</td>
                            <td class="num text-xs font-medium text-slate-700">{{ $baris['masuk'] ?? '—' }}</td>
                            <td class="num text-xs font-medium text-slate-700">{{ $baris['pulang'] ?? '—' }}</td>
                            <td class="num text-xs text-slate-500">{{ $baris['jumlahTap'] }}</td>
                            <td>
                                @if ($baris['status'] === 'Hadir')
                                    <span class="badge badge-success">
                                        <span class="h-1.5 w-1.5 rounded-full bg-sukses-600"></span>
                                        Hadir
                                    </span>
                                @elseif ($baris['status'] === 'Terlambat')
                                    <span class="badge badge-warning">Terlambat</span>
                                @else
                                    <span class="badge badge-danger">Alpha</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-sm text-slate-500">
                                Belum ada data presensi siswa
                                @if ($search !== '')
                                    untuk pencarian "{{ $search }}"
                                @endif
                                pada tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
            Hanya siswa yang sudah punya ID mesin absensi yang tampil. Siswa tanpa ID mesin
            dapat didaftarkan lewat menu Data Pengguna.
        </p>
    </div>

    @if ($presensi->hasPages())
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500">Menampilkan <span class="num font-medium text-slate-700">{{ $presensi->firstItem() ?? 0 }}</span>&ndash;<span class="num font-medium text-slate-700">{{ $presensi->lastItem() ?? 0 }}</span> dari <span class="num font-medium text-slate-700">{{ $presensi->total() }}</span> data presensi murid</p>
            <div>{{ $presensi->links() }}</div>
        </div>
    @endif

    <x-tap-belum-terdaftar :taps="$belumTerdaftar" />
</div>
