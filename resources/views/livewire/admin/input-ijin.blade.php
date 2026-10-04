<div class="space-y-6">
    <div>
        <h1 class="page-title">Catat Pengajuan Izin</h1>
        <p class="page-subtitle">
            Catat izin atau sakit siswa di behalf orang tua, lalu proses di halaman konfirmasi.
        </p>
    </div>

    @if (session()->has('message'))
        <div class="card flex items-center gap-2 border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700" role="alert">
            <x-icon name="check" class="h-4 w-4 shrink-0" />
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <div class="card">
                <div class="card-header">
                    <h2 class="section-title">Form Pengajuan</h2>
                </div>

                <div class="card-body">
                    <form wire:submit.prevent="submit" class="space-y-4">
                        <div>
                            <label for="cariSiswa" class="label">Cari Siswa</label>
                            <input id="cariSiswa" type="search" wire:model.live.debounce.300ms="cariSiswa"
                                   class="input" placeholder="Nama atau NIS">
                        </div>

                        <div>
                            <label for="siswa_id" class="label">Siswa</label>
                            <select id="siswa_id" wire:model="siswa_id" class="input @error('siswa_id') input-error @enderror">
                                <option value="">-- Pilih siswa --</option>
                                @foreach ($siswaList as $siswa)
                                    <option value="{{ $siswa->id }}">{{ $siswa->nama }} &middot; {{ $siswa->nis }}</option>
                                @endforeach
                            </select>
                            @error('siswa_id') <span class="help-error">{{ $message }}</span> @enderror
                        </div>

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
                            Simpan Pengajuan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="table-shell">
                <div class="card-header">
                    <h2 class="section-title">Pengajuan Terbaru</h2>
                    <a href="{{ route('admin.konfirmasi') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">
                        Ke halaman konfirmasi
                    </a>
                </div>

                <div class="table-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>Jenis</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($riwayat as $item)
                                <tr wire:key="ijin-{{ $item->id }}">
                                    <td class="font-medium text-slate-800">{{ $item->siswa?->nama ?? 'Siswa dihapus' }}</td>
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
                                    <td colspan="4" class="py-10 text-center text-sm text-slate-500">
                                        Belum ada pengajuan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($riwayat->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">{{ $riwayat->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>