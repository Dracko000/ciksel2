<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Monitoring Device ZKTeco</h2>
            <p class="page-subtitle">Pantau status koneksi dan kelola identitas mesin absensi Anda.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-neutral">{{ $devices->count() }} mesin terdaftar</span>
            @if ($isEdit)
                <span class="badge badge-brand">Mode ubah data</span>
            @endif
        </div>
    </div>

<x-flash-toast />

    @if ($errors->any())
        <div class="card border-bahaya-100 bg-bahaya-50 p-4" role="alert">
            <div class="flex items-start gap-3">
                <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0 text-bahaya-700" />
                <ul class="space-y-1 text-sm font-medium text-bahaya-800">
                    @foreach ($errors->all() as $error)
                        <li wire:key="error-{{ $loop->index }}">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <div class="card">
                <div class="card-header">
                    <h3 class="section-title">
                        {{ $isEdit ? 'Edit Device' : 'Tambah Device Manual' }}
                    </h3>
                    <x-icon name="device" class="h-4 w-4 text-slate-400" />
                </div>

                <div class="card-body">
                    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="space-y-4">
                        <div>
                            <label for="no_sn" class="label">Serial Number (SN)</label>
                            <input
                                id="no_sn"
                                wire:model="no_sn"
                                type="text"
                                placeholder="Masukkan SN Mesin"
                                class="input font-mono @error('no_sn') input-error @enderror {{ $isEdit ? 'opacity-50 pointer-events-none' : '' }}"
                            >
                            @error('no_sn')
                                <p class="help-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="nama" class="label">Nama Mesin</label>
                            <input
                                id="nama"
                                wire:model="nama"
                                type="text"
                                placeholder="Contoh: Pintu Depan"
                                class="input"
                            >
                        </div>

                        <div>
                            <label for="lokasi" class="label">Lokasi</label>
                            <input
                                id="lokasi"
                                wire:model="lokasi"
                                type="text"
                                placeholder="Contoh: Gedung A Lt.1"
                                class="input"
                            >
                        </div>

                        <div>
                            <label for="secret_token" class="label">Secret Token</label>
                            <div class="flex gap-2">
                                <input
                                    id="secret_token"
                                    wire:model="secret_token"
                                    type="text"
                                    placeholder="Kosongkan bila device tanpa token"
                                    class="input flex-1 font-mono"
                                >
                                <button type="button" wire:click="generateToken" class="btn btn-secondary shrink-0">
                                    Generate
                                </button>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500">
                                Device wajib mengirim token ini lewat header <span class="font-mono">X-Device-Token</span>.
                                Biarkan kosong hanya untuk device uji.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                            <button type="submit" class="btn btn-primary flex-1">
                                <x-icon name="check" class="h-4 w-4" />
                                {{ $isEdit ? 'Update' : 'Simpan Device' }}
                            </button>
                            @if ($isEdit)
                                <button type="button" wire:click="resetFields" class="btn btn-secondary">
                                    Batal
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="table-shell">
                <div class="card-header">
                    <h3 class="section-title">Daftar Mesin</h3>
                    <span class="text-xs text-slate-400">Terakhir kontak &lt; 5 menit ditandai online</span>
                </div>

                <div class="table-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Serial Number (SN)</th>
                                <th>Identitas</th>
                                <th>Status Terakhir</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($devices as $dev)
                                @php
                                    $isOnline = $dev->online ? \Carbon\Carbon::parse($dev->online)->diffInMinutes(now()) < 5 : false;
                                @endphp
                                <tr wire:key="device-{{ $dev->id }}">
                                    <td>
                                        <span class="num rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ $dev->no_sn }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="block font-medium text-slate-900">{{ $dev->nama ?? 'Unit Belum Dinamai' }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $dev->lokasi ?? 'Lokasi Belum Diatur' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $isOnline ? 'badge-success' : 'badge-danger' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $isOnline ? 'bg-sukses-600' : 'bg-bahaya-600' }}"></span>
                                            {{ $isOnline ? 'Online' : 'Offline' }}
                                        </span>
                                        <span class="mt-1 block text-xs text-slate-400">
                                            Sinyal: {{ $dev->online ? \Carbon\Carbon::parse($dev->online)->diffForHumans() : 'Belum pernah konek' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                wire:click="bukaPerintah({{ $dev->id }})"
                                                class="btn btn-sm btn-secondary"
                                            >
                                                <x-icon name="terminal" class="h-3.5 w-3.5" />
                                                Perintah
                                                @if (($dev->antrean ?? 0) > 0)
                                                    <span class="ml-1 rounded-full bg-brand-100 px-1.5 py-0.5 text-[10px] font-bold text-brand-700">
                                                        {{ $dev->antrean }}
                                                    </span>
                                                @endif
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="edit({{ $dev->id }})"
                                                class="btn btn-sm btn-secondary"
                                            >
                                                <x-icon name="edit" class="h-3.5 w-3.5" />
                                                Ubah
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="delete({{ $dev->id }})"
                                                onclick="confirm('Hapus device ini dari monitoring?') || event.stopImmediatePropagation()"
                                                class="btn btn-sm btn-danger"
                                            >
                                                <x-icon name="trash" class="h-3.5 w-3.5" />
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-sm text-slate-500">
                                        Belum ada mesin ZKTeco yang terhubung.
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
    @if ($perintahDevice)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:items-center"
             role="dialog" aria-modal="true" aria-labelledby="perintah-mesin-judul">
            <div class="w-full max-w-2xl rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5">
                    <div class="min-w-0">
                        <h3 id="perintah-mesin-judul" class="text-base font-semibold text-slate-900">
                            Perintah untuk {{ $perintahDevice->nama ?? 'mesin' }}
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            SN <span class="num font-semibold text-slate-700">{{ $perintahDevice->no_sn }}</span>
                            &middot; perintah dijalankan saat mesin polling berikutnya
                        </p>
                    </div>

                    <button type="button" wire:click="tutupPerintah" class="btn btn-sm btn-secondary" aria-label="Tutup">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-5 p-5">
                    <div class="flex items-start gap-3 rounded-lg border border-bahaya-100 bg-bahaya-50 p-3">
                        <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0 text-bahaya-700" />
                        <p class="text-xs font-medium text-bahaya-800">
                            Perintah di bawah menghapus data di mesin dan tidak bisa dibatalkan dari aplikasi.
                            Data absensi yang sudah masuk ke server tetap aman, tetapi tap yang belum terkirim
                            akan hilang.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="perintahJenis" class="label">Jenis perintah</label>
                            <select id="perintahJenis" wire:model.live="perintahJenis" class="select">
                                <option value="hapus_user">Hapus satu user (PIN)</option>
                                <option value="hapus_log">Bersihkan log absensi mesin</option>
                                <option value="hapus_semua_user">Hapus SEMUA user di mesin</option>
                                <option value="reboot">Restart mesin</option>
                            </select>
                        </div>

                        @if ($perintahJenis === 'hapus_user')
                            <div>
                                <label for="perintahPin" class="label">PIN / Employee ID</label>
                                <input
                                    id="perintahPin"
                                    wire:model="perintahPin"
                                    type="text"
                                    inputmode="numeric"
                                    placeholder="Contoh: 2"
                                    class="input font-mono @error('perintahPin') input-error @enderror"
                                >
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Sama dengan ID mesin pada data pengguna.
                                </p>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="konfirmasi" class="label">
                            Ketik SN mesin untuk konfirmasi
                        </label>
                        <input
                            id="konfirmasi"
                            wire:model="konfirmasi"
                            type="text"
                            placeholder="{{ $perintahDevice->no_sn }}"
                            class="input font-mono @error('konfirmasi') input-error @enderror"
                            autocomplete="off"
                        >
                        @error('konfirmasi')
                            <p class="help-error">{{ $message }}</p>
                        @enderror
                        @error('perintah')
                            <p class="help-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="border-t border-slate-100 pt-4">
                        <div class="flex items-center justify-between">
                            <h4 class="section-title">Riwayat perintah</h4>
                            <button type="button" wire:click="muatRiwayat" class="btn btn-sm btn-secondary">
                                <x-icon name="refresh" class="h-3.5 w-3.5" />
                                Muat ulang
                            </button>
                        </div>

                        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Perintah</th>
                                        <th>Status</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($riwayat as $cmd)
                                        @php
                                            $statusBadge = match ($cmd->status) {
                                                'done' => 'badge-success',
                                                'failed' => 'badge-danger',
                                                'sent' => 'badge-warning',
                                                default => 'badge-neutral',
                                            };
                                            $statusLabel = match ($cmd->status) {
                                                'done' => 'Berhasil',
                                                'failed' => 'Gagal',
                                                'sent' => 'Dikirim',
                                                default => 'Menunggu',
                                            };
                                        @endphp
                                        <tr wire:key="cmd-{{ $cmd->id }}">
                                            <td class="font-mono text-xs text-slate-700">{{ $cmd->command }}</td>
                                            <td>
                                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                                @if (filled($cmd->return_code))
                                                    <span class="mt-1 block text-[11px] text-slate-400">
                                                        Return {{ $cmd->return_code }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-xs text-slate-500">
                                                {{ ($cmd->finished_at ?? $cmd->sent_at ?? $cmd->created_at)?->diffForHumans() }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-6 text-center text-xs text-slate-500">
                                                Belum ada perintah untuk mesin ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 p-5">
                    <button type="button" wire:click="tutupPerintah" class="btn btn-secondary">
                        Batal
                    </button>
                    <button type="button" wire:click="kirimPerintah" class="btn btn-danger">
                        <x-icon name="terminal" class="h-4 w-4" />
                        Kirim ke antrean
                    </button>
                </div>
            </div>
        </div>
    @endif
