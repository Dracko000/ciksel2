<div class="space-y-6">
    <div>
        <h1 class="page-title">Absensi Kelas</h1>
        <p class="page-subtitle">Lihat absen masuk dan pulang siswa per kelas.</p>
    </div>

    <div class="card p-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="kelas_id" class="label">Pilih Kelas</label>
                <select id="kelas_id" wire:model.live="kelas_id" class="input">
                    @foreach ($kelasList as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="tanggal" class="label">Pilih Tanggal</label>
                <input id="tanggal" type="date" wire:model.live="tanggal" class="input">
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Data Absensi {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d M Y') }}</h2>
        </div>
        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>Absen Masuk</th>
                        <th>Absen Pulang</th>
                        <th>Total Tap</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswaList as $siswa)
                        @php $data = $absensiBySiswa->get($siswa->id); @endphp
                        <tr>
                            <td>{{ $siswa->nama }}</td>
                            <td class="num">{{ $siswa->nis }}</td>
                            <td>
                                @if ($data && $data['masuk'])
                                    <span class="badge badge-success">
                                        {{ \Carbon\Carbon::parse($data['masuk']->timestamp)->format('H:i:s') }}
                                    </span>
                                @else
                                    <span class="text-sm text-red-600">Belum absen</span>
                                @endif
                            </td>
                            <td>
                                @if ($data && $data['pulang'])
                                    <span class="badge badge-brand">
                                        {{ \Carbon\Carbon::parse($data['pulang']->timestamp)->format('H:i:s') }}
                                    </span>
                                @else
                                    <span class="text-sm text-slate-500">-</span>
                                @endif
                            </td>
                            <td class="num">{{ $data['total'] ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-sm text-slate-500">Tidak ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
