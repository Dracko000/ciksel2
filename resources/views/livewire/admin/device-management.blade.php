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

    @if (session()->has('message'))
        <div class="card flex items-start gap-3 border-emerald-200 bg-emerald-50 p-4" role="alert">
            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
            <p class="text-sm font-medium text-emerald-800">{{ session('message') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="card border-rose-200 bg-rose-50 p-4" role="alert">
            <div class="flex items-start gap-3">
                <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" />
                <ul class="space-y-1 text-sm font-medium text-rose-700">
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
                                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-semibold text-slate-700">
                                            {{ $dev->no_sn }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="block font-medium text-slate-800">{{ $dev->nama ?? 'Unit Belum Dinamai' }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $dev->lokasi ?? 'Lokasi Belum Diatur' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $isOnline ? 'badge-success' : 'badge-danger' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
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