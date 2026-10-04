<div class="space-y-6">
    <div>
        <h2 class="page-title">Absensi Kelas (Wali Kelas)</h2>
        <p class="page-subtitle">
            Pantau kehadiran siswa per kelas dan per tanggal, termasuk pengajuan izin yang disetujui.
        </p>
    </div>

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="kelas_id" class="label">Pilih Kelas</label>
                <select id="kelas_id" wire:model.live="kelas_id" class="input">
                    @foreach ($kelasList as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="tanggal" class="label">Tanggal</label>
                <input id="tanggal" type="date" wire:model.live="tanggal" class="input">
            </div>
        </div>
    </div>

    <div class="table-shell">
        <div class="card-header">
            <h3 class="section-title">Daftar Kehadiran Siswa</h3>
            <span class="badge badge-neutral">{{ $siswaList->count() }} siswa</span>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Waktu Hadir</th>
                        <th>Status Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswaList as $siswa)
                        @php
                            $hadir = isset($attendances[$siswa->wajah_id_zkteco]);
                            $waktuHadir = $hadir
                                ? \Carbon\Carbon::parse($attendances[$siswa->wajah_id_zkteco]->timestamp)->format('H:i:s')
                                : '-';

                            // Cek pengajuan ijin (logic sederhana)
                            $ijin = App\Models\PengajuanIjin::where('siswa_id', $siswa->id)
                                    ->where('tanggal_mulai', '<=', $this->tanggal)
                                    ->where('tanggal_selesai', '>=', $this->tanggal)
                                    ->where('status', 'disetujui')
                                    ->first();

                            if ($hadir) {
                                $statusLabel = 'Hadir (Mesin)';
                                $statusBadge = 'badge-success';
                            } elseif ($ijin) {
                                $statusLabel = ucfirst((string) $ijin->jenis);
                                $statusBadge = 'badge-warning';
                            } else {
                                $statusLabel = 'Belum Hadir / Alpha';
                                $statusBadge = 'badge-danger';
                            }
                        @endphp
                        <tr wire:key="absensi-siswa-{{ $siswa->id }}">
                            <td class="font-mono text-xs">{{ $siswa->nis }}</td>
                            <td class="font-medium text-slate-800">{{ $siswa->nama }}</td>
                            <td class="whitespace-nowrap font-mono text-xs text-slate-500">{{ $waktuHadir }}</td>
                            <td>
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-sm text-slate-500">
                                Pilih kelas atau data siswa kosong.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>