<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Konfirmasi Izin / Sakit</h2>
            <p class="page-subtitle">
                Pengajuan izin dan sakit siswa yang menunggu keputusan Anda.
            </p>
        </div>

        <div class="w-full sm:w-64">
            <label for="kelasFilter" class="label">Kelas</label>
            <select id="kelasFilter" wire:model.live="kelasFilter" class="input">
                <option value="">Semua Kelas</option>
                @foreach ($kelasList as $kelas)
                    <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-flash-toast />

    <div class="table-shell">
        <div class="card-header">
            <h3 class="section-title">Pengajuan Izin</h3>
            <div class="flex items-center gap-2">
                @if ($kelasFilter !== '')
                    <span class="badge badge-brand">
                        {{ $kelasList->firstWhere('id', $kelasFilter)?->nama_kelas ?? 'Kelas' }}
                    </span>
                @endif
                <span class="badge badge-neutral">{{ $pengajuan->total() }} pengajuan</span>
            </div>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Jenis</th>
                        <th>Keterangan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pengajuan as $item)
                        <tr wire:key="ijin-{{ $item->id }}">
                            <td class="font-medium text-maroon-50">{{ $item->siswa->nama }}</td>
                            <td class="text-xs text-maroon-100">{{ $item->siswa->kelas?->nama_kelas ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $item->jenis === 'sakit' ? 'badge-danger' : 'badge-warning' }}">
                                    {{ ucfirst($item->jenis) }}
                                </span>
                            </td>
                            <td class="max-w-xs text-sm text-maroon-100">{{ $item->keterangan }}</td>
                            <td class="whitespace-nowrap num text-xs text-maroon-100">
                                {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') }}
                                @if ($item->tanggal_selesai && $item->tanggal_selesai !== $item->tanggal_mulai)
                                    &ndash; {{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') }}
                                @endif
                            </td>
                            <td>
                                @if ($item->status === 'pending')
                                    <span class="badge badge-warning">Menunggu</span>
                                @elseif ($item->status === 'disetujui')
                                    <span class="badge badge-success">Disetujui</span>
                                @else
                                    <span class="badge badge-danger">Ditolak</span>
                                @endif
                            </td>
                            <td>
                                @if ($item->status === 'pending')
                                    <div class="flex gap-2">
                                        <button type="button"
                                            wire:click="approve({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="approve({{ $item->id }})"
                                            class="btn btn-success btn-sm">
                                            Setuju
                                        </button>
                                        <button type="button"
                                            wire:click="reject({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="reject({{ $item->id }})"
                                            class="btn btn-danger btn-sm">
                                            Tolak
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-maroon-300">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-sm text-maroon-200">
                                @if ($kelasFilter !== '')
                                    Tidak ada pengajuan izin pada kelas
                                    {{ $kelasList->firstWhere('id', $kelasFilter)?->nama_kelas ?? 'terpilih' }}.
                                @else
                                    Belum ada pengajuan izin.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pengajuan->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-maroon-200">Menampilkan <span class="num font-medium text-maroon-100">{{ $pengajuan->firstItem() ?? 0 }}</span>&ndash;<span class="num font-medium text-maroon-100">{{ $pengajuan->lastItem() ?? 0 }}</span> dari <span class="num font-medium text-maroon-100">{{ $pengajuan->total() }}</span> pengajuan</p>
                    <div>{{ $pengajuan->links() }}</div>
                </div>
            </div>
        @endif
    </div>
</div>