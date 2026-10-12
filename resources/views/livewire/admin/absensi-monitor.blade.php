<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Monitor Absensi Harian</h2>
            <p class="page-subtitle">
                Rekap tap mesin ZKTeco per orang pada tanggal yang dipilih, dikelompokkan per kelas.
            </p>
        </div>

        <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-3 lg:w-auto lg:min-w-[30rem]">
            <div>
                <label for="kelasFilter" class="label">Kelas</label>
                <select id="kelasFilter" wire:model.live="kelasFilter" class="input">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelasList as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="roleFilter" class="label">Peran</label>
                <select id="roleFilter" wire:model.live="roleFilter" class="input">
                    <option value="">Semua Peran</option>
                    <option value="siswa">Siswa</option>
                    <option value="guru">Guru/Tendik</option>
                </select>
            </div>

            <div>
                <label for="dateFilter" class="label">Pilih Tanggal</label>
                <input id="dateFilter" type="date" wire:model.live="dateFilter" class="input">
            </div>
        </div>
    </div>

    <div class="table-shell">
        <div class="card-header">
            <div>
                <h3 class="section-title">Rekap Absensi</h3>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($kelasFilter !== '')
                    <span class="badge badge-brand">
                        {{ $kelasList->firstWhere('id', $kelasFilter)?->nama_kelas ?? 'Kelas' }}
                    </span>
                @endif
                @if ($roleFilter !== '')
                    <span class="badge badge-neutral">
                        {{ $roleFilter === 'siswa' ? 'Siswa' : 'Guru/Tendik' }}
                    </span>
                @endif
                <span class="badge badge-success">
                    <span class="h-1.5 w-1.5 rounded-full bg-sukses-600"></span>
                    {{ $hadir }} hadir
                </span>
                <span class="badge badge-warning">{{ $terlambat }} terlambat</span>
                <span class="badge badge-danger">{{ $alpha }} alpha</span>
                <span class="badge badge-neutral">{{ $attendances->total() }} data</span>

                <span class="mx-1 hidden h-6 w-px bg-slate-200 sm:block"></span>

                <div class="flex flex-wrap items-center gap-1.5">
                    <a class="btn btn-sm btn-secondary"
                       href="{{ route('admin.absensi.download', array_filter(['periode' => 'harian', 'tanggal' => $dateFilter, 'kelas_id' => $kelasFilter, 'peran' => $roleFilter])) }}">
                        <x-icon name="download" class="h-3.5 w-3.5" />
                        Harian
                    </a>
                    <a class="btn btn-sm btn-secondary"
                       href="{{ route('admin.absensi.download', array_filter(['periode' => 'mingguan', 'tanggal' => $dateFilter, 'kelas_id' => $kelasFilter, 'peran' => $roleFilter])) }}">
                        <x-icon name="download" class="h-3.5 w-3.5" />
                        Mingguan
                    </a>
                    <a class="btn btn-sm btn-secondary"
                       href="{{ route('admin.absensi.download', array_filter(['periode' => 'bulanan', 'tanggal' => $dateFilter, 'kelas_id' => $kelasFilter, 'peran' => $roleFilter])) }}">
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
                        <th>NIS/NIK/NIP</th>
                        <th>ID Mesin</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Jumlah Masuk</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $kelompokTerakhir = null;
                        $nomor = $attendances->firstItem() ?? 0;
                    @endphp

                    @forelse ($attendances as $baris)
                        @if ($baris['kelompok'] !== $kelompokTerakhir)
                            @php $kelompokTerakhir = $baris['kelompok']; @endphp
                            <tr class="group-row" wire:key="kelompok-{{ \Illuminate\Support\Str::slug($baris['kelompok']) }}">
                                <td colspan="8">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                                        {{ $baris['kelompok'] }}
                                    </span>
                                </td>
                            </tr>
                        @endif

                        @php $nomor++; @endphp
                        <tr wire:key="absensi-{{ $baris['employee_id'] }}">
                            <td class="num text-xs text-slate-400">{{ $nomor }}</td>
                            <td class="text-xs font-medium text-slate-800">{{ $baris['nama'] }}</td>
                            <td class="num text-xs text-slate-500">{{ $baris['nis'] }}</td>
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
                            <td colspan="8" class="py-10 text-center text-sm text-slate-500">
                                Belum ada data absensi
                                @if ($kelasFilter !== '' || $roleFilter !== '')
                                    pada filter yang dipilih
                                    @if ($kelasFilter !== '')
                                        kelas {{ $kelasList->firstWhere('id', $kelasFilter)?->nama_kelas ?? 'terpilih' }}
                                    @endif
                                    @if ($roleFilter !== '')
                                        peran {{ $roleFilter === 'siswa' ? 'Siswa' : 'Guru/Tendik' }}
                                    @endif
                                @else
                                    untuk tanggal ini
                                @endif
                                .
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($attendances->hasPages())
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500">Menampilkan <span class="num font-medium text-slate-700">{{ $attendances->firstItem() ?? 0 }}</span>&ndash;<span class="num font-medium text-slate-700">{{ $attendances->lastItem() ?? 0 }}</span> dari <span class="num font-medium text-slate-700">{{ $attendances->total() }}</span> data absensi harian</p>
            <div>{{ $attendances->links() }}</div>
        </div>
    @endif
</div>
