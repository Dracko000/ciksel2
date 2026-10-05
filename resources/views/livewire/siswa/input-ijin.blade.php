<div class="space-y-6">
    <div>
        <h1 class="page-title">Pengajuan Izin</h1>
        <p class="page-subtitle">
            Ajukan izin atau sakit. Orang tua dapat mengisi halaman ini memakai akun siswa.
        </p>
    </div>

<x-flash-toast />

    @if (! $punyaDataSiswa)
        <div class="card flex items-start gap-3 border-peringatan-100 bg-peringatan-50 p-4 text-sm text-peringatan-800" role="alert">
            <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>Akun ini belum terhubung ke data siswa, jadi pengajuan belum bisa dibuat. Hubungi administrator sekolah.</span>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <div class="card">
                    <div class="card-header">
                        <h2 class="section-title">Form Pengajuan</h2>
                    </div>

                    <div class="card-body">
                        <form wire:submit.prevent="submit" class="space-y-4">
                            <fieldset>
                                <legend class="label">Jenis Pengajuan</legend>
                                <div class="flex items-center gap-6">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" wire:model="jenis" value="sakit" class="accent-brand-600">
                                        Sakit
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" wire:model="jenis" value="izin" class="accent-brand-600">
                                        Izin
                                    </label>
                                </div>
                                @error('jenis') <span class="help-error">{{ $message }}</span> @enderror
                            </fieldset>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="tanggal_mulai" class="label">Dari Tanggal</label>
                                    <input id="tanggal_mulai" type="date" wire:model="tanggal_mulai"
                                           class="input @error('tanggal_mulai') input-error @enderror">
                                    @error('tanggal_mulai') <span class="help-error">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="tanggal_selesai" class="label">Sampai Tanggal</label>
                                    <input id="tanggal_selesai" type="date" wire:model="tanggal_selesai"
                                           class="input @error('tanggal_selesai') input-error @enderror">
                                    @error('tanggal_selesai') <span class="help-error">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label for="keterangan" class="label">Alasan / Keterangan</label>
                                <textarea id="keterangan" wire:model="keterangan" rows="3"
                                          class="input @error('keterangan') input-error @enderror"
                                          placeholder="Contoh: demam sejak kemarin sore."></textarea>
                                @error('keterangan') <span class="help-error">{{ $message }}</span> @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                                <x-icon name="mail" class="h-4 w-4" />
                                Kirim Pengajuan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="table-shell">
                    <div class="card-header">
                        <h2 class="section-title">Riwayat Pengajuan</h2>
                        <span class="badge badge-neutral">{{ $riwayat->total() }} pengajuan</span>
                    </div>

                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($riwayat as $item)
                                    <tr wire:key="ijin-{{ $item->id }}">
                                        <td>
                                            <span @class([
                                                'badge badge-danger' => $item->jenis === 'sakit',
                                                'badge badge-warning' => $item->jenis !== 'sakit',
                                            ])>{{ ucfirst((string) $item->jenis) }}</span>
                                        </td>
                                        <td class="whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') }}
                                            &ndash;
                                            {{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') }}
                                        </td>
                                        <td class="max-w-xs">{{ $item->keterangan }}</td>
                                        <td>
                                            <span @class([
                                                'badge badge-warning' => $item->status === 'pending',
                                                'badge badge-success' => $item->status === 'disetujui',
                                                'badge badge-danger' => $item->status === 'ditolak',
                                            ])>{{ ucfirst((string) $item->status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-10 text-center text-sm text-maroon-200">
                                            Belum ada pengajuan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($riwayat->hasPages())
                        <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-maroon-200">Menampilkan <span class="num font-medium text-maroon-100">{{ $riwayat->firstItem() ?? 0 }}</span>&ndash;<span class="num font-medium text-maroon-100">{{ $riwayat->lastItem() ?? 0 }}</span> dari <span class="num font-medium text-maroon-100">{{ $riwayat->total() }}</span> pengajuan</p>
                            <div>{{ $riwayat->links() }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>