<div>
    <h2 class="text-2xl font-bold mb-4">Absensi Kelas (Wali Kelas)</h2>

    <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4 mb-6 bg-white p-4 rounded shadow-sm border">
        <div>
            <label class="block text-sm font-medium text-gray-700">Pilih Kelas</label>
            <select wire:model.live="kelas_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border">
                @foreach($kelasList as $kelas)
                    <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
            <input type="date" wire:model.live="tanggal" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border">
        </div>
    </div>

    <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
        <table class="text-left w-full border-collapse">
            <thead>
                <tr>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">NIS</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Nama Siswa</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Waktu Hadir</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Status Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswaList as $siswa)
                    @php
                        $hadir = isset($attendances[$siswa->wajah_id_zkteco]);
                        $waktuHadir = $hadir ? \Carbon\Carbon::parse($attendances[$siswa->wajah_id_zkteco]->timestamp)->format('H:i:s') : '-';
                        
                        // Cek pengajuan ijin (logic sederhana)
                        $ijin = App\Models\PengajuanIjin::where('siswa_id', $siswa->id)
                                ->where('tanggal_mulai', '<=', $this->tanggal)
                                ->where('tanggal_selesai', '>=', $this->tanggal)
                                ->where('status', 'disetujui')
                                ->first();
                        
                        if ($hadir) {
                            $statusBadge = '<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">Hadir (Mesin)</span>';
                        } elseif ($ijin) {
                            $statusBadge = '<span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded">'.ucfirst($ijin->jenis).'</span>';
                        } else {
                            $statusBadge = '<span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded">Belum Hadir / Alpha</span>';
                        }
                    @endphp
                <tr class="hover:bg-grey-lighter">
                    <td class="py-4 px-6 border-b border-grey-light">{{ $siswa->nis }}</td>
                    <td class="py-4 px-6 border-b border-grey-light">{{ $siswa->nama }}</td>
                    <td class="py-4 px-6 border-b border-grey-light">{{ $waktuHadir }}</td>
                    <td class="py-4 px-6 border-b border-grey-light">{!! $statusBadge !!}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-4 px-6 text-center text-gray-500">Pilih kelas atau data siswa kosong.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
