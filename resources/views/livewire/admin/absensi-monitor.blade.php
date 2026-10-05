<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Monitor Absensi Harian</h2>
            <p class="page-subtitle">
                Rekapitulasi tap mesin ZKTeco berdasarkan tanggal yang dipilih.
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
            <h3 class="section-title">Log Absensi Mesin</h3>
            <div class="flex items-center gap-2">
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
                <span class="badge badge-neutral">{{ $attendances->total() }} catatan</span>
            </div>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu Tap</th>
                        <th>Device SN</th>
                        <th>ZKTeco ID</th>
                        <th>Teridentifikasi Sebagai</th>
                        <th>Kelas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $log)
                        @php
                            $identitas = 'Tidak Dikenal (Unregistered)';
                            $badge = 'badge-neutral';
                            $kelas = null;

                            if (isset($siswas[$log->employee_id])) {
                                $identitas = $siswas[$log->employee_id]->nama . ' (Siswa)';
                                $badge = 'badge-brand';
                                $kelas = $siswas[$log->employee_id]->kelas?->nama_kelas;
                            } elseif (isset($gurus[$log->employee_id])) {
                                $identitas = $gurus[$log->employee_id]->nama . ' (Guru/Tendik)';
                                $badge = 'badge-success';
                            }
                        @endphp
                        <tr wire:key="absensi-{{ $log->id }}">
                            <td class="whitespace-nowrap num text-xs">
                                {{ \Carbon\Carbon::parse($log->timestamp)->format('H:i:s') }}
                            </td>
                            <td class="num text-xs">{{ $log->sn }}</td>
                            <td class="num text-xs font-medium text-maroon-200">{{ $log->employee_id }}</td>
                            <td>
                                <span class="badge {{ $badge }}">{{ $identitas }}</span>
                            </td>
                            <td class="text-xs text-maroon-100">{{ $kelas ?? '-' }}</td>
                            <td>
                                <span class="badge badge-success">
                                    <span class="h-1.5 w-1.5 rounded-full bg-sukses-600"></span>
                                    Hadir
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-sm text-maroon-200">
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
            <p class="text-xs text-maroon-200">Menampilkan <span class="num font-medium text-maroon-100">{{ $attendances->firstItem() ?? 0 }}</span>&ndash;<span class="num font-medium text-maroon-100">{{ $attendances->lastItem() ?? 0 }}</span> dari <span class="num font-medium text-maroon-100">{{ $attendances->total() }}</span> log absensi</p>
            <div>{{ $attendances->links() }}</div>
        </div>
    @endif
</div>