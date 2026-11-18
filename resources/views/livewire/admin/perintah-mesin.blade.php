<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Perintah Mesin</h2>
            <p class="page-subtitle">
                Kirim perintah ke mesin ZKTeco: hapus user, bersihkan log, atau restart.
                Mesin menjalankannya sendiri saat polling berikutnya.
            </p>
        </div>

        <span class="badge badge-neutral">{{ $devices->count() }} mesin terdaftar</span>
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

    @if ($devices->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                    <x-icon name="device" class="h-6 w-6" />
                </span>
                <p class="empty-state-title">Belum ada mesin terdaftar</p>
                <p class="empty-state-text">
                    Tambahkan mesin ZKTeco lebih dulu di halaman Mesin Absensi.
                </p>
                <a href="{{ route('admin.devices') }}" class="btn btn-primary mt-4">
                    <x-icon name="device" class="h-4 w-4" />
                    Buka Mesin Absensi
                </a>
            </div>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Kolom kiri: pilih mesin --}}
            <div class="space-y-3 lg:col-span-1">
                <h3 class="section-title">Pilih mesin</h3>

                @foreach ($devices as $dev)
                    @php
                        $online = $dev->online ? \Carbon\Carbon::parse($dev->online)->diffInMinutes(now()) < 5 : false;
                    @endphp
                    <button
                        type="button"
                        wire:click="pilihDevice({{ $dev->id }})"
                        wire:key="pilih-device-{{ $dev->id }}"
                        @class([
                            'w-full rounded-xl border p-4 text-left transition',
                            'border-brand-500 bg-brand-50/60 shadow-sm shadow-brand-600/10' => $deviceId === $dev->id,
                            'border-slate-200 bg-white hover:border-slate-300' => $deviceId !== $dev->id,
                        ])
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">
                                    {{ $dev->nama ?? 'Mesin tanpa nama' }}
                                </p>
                                <p class="num mt-0.5 text-xs text-slate-500">{{ $dev->no_sn }}</p>
                            </div>

                            <span class="badge {{ $online ? 'badge-success' : 'badge-danger' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $online ? 'bg-sukses-600' : 'bg-bahaya-600' }}"></span>
                                {{ $online ? 'Online' : 'Offline' }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            @if ($dev->antrean > 0)
                                <span class="badge badge-warning">{{ $dev->antrean }} perintah menunggu</span>
                            @else
                                <span class="badge badge-neutral">Antrean kosong</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>

            {{-- Kolom kanan: form perintah --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="section-title">Kirim perintah</h3>
                        @if ($this->device)
                            <span class="num badge badge-neutral">{{ $this->device->no_sn }}</span>
                        @endif
                    </div>

                    <div class="card-body space-y-5">
                        <div class="flex items-start gap-3 rounded-lg border border-bahaya-100 bg-bahaya-50 p-3">
                            <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0 text-bahaya-700" />
                            <p class="text-xs font-medium text-bahaya-800">
                                Perintah di halaman ini menghapus data di mesin dan tidak bisa dibatalkan.
                                Data absensi yang sudah masuk ke server tetap aman, tetapi tap yang belum
                                terkirim akan hilang. Wajib ketik SN mesin untuk konfirmasi.
                            </p>
                        </div>

                        <div>
                            <label for="jenis" class="label">Jenis perintah</label>
                            <select id="jenis" wire:model.live="jenis" class="select">
                                <option value="hapus_user">Hapus satu user (PIN)</option>
                                <option value="hapus_log">Bersihkan log absensi mesin</option>
                                <option value="hapus_semua_user">Hapus SEMUA user di mesin</option>
                                <option value="reboot">Restart mesin</option>
                            </select>
                        </div>

                        @if ($jenis === 'hapus_user')
                            <div class="rounded-xl border border-slate-200">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Pilih pengguna</p>
                                        <p class="text-xs text-slate-500">
                                            Berdasarkan tap yang pernah masuk dari mesin ini.
                                        </p>
                                    </div>

                                    <div class="relative">
                                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                        <input
                                            wire:model.live.debounce.300ms="cariPengguna"
                                            type="search"
                                            placeholder="Cari nama atau PIN"
                                            class="input h-9 w-56 pl-9"
                                        >
                                    </div>
                                </div>

                                <div class="max-h-64 overflow-y-auto">
                                    @forelse ($this->penggunaMesin as $orang)
                                        <button
                                            type="button"
                                            wire:click="pilihPengguna('{{ $orang['pin'] }}')"
                                            wire:key="pengguna-{{ $orang['pin'] }}"
                                            @class([
                                                'flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5 text-left transition last:border-b-0',
                                                'bg-brand-50/70' => $pin === $orang['pin'],
                                                'hover:bg-slate-50' => $pin !== $orang['pin'],
                                            ])
                                        >
                                            <span class="flex min-w-0 items-center gap-2">
                                                <span class="num rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                                    {{ $orang['pin'] }}
                                                </span>
                                                <span class="truncate text-sm font-medium text-slate-800">
                                                    {{ $orang['nama'] ?? 'Tidak dikenal' }}
                                                </span>
                                                @if ($orang['tipe'])
                                                    <span class="badge badge-neutral">{{ $orang['tipe'] }}</span>
                                                @endif
                                                @if ($orang['kelas'])
                                                    <span class="badge badge-neutral">{{ $orang['kelas'] }}</span>
                                                @endif
                                            </span>

                                            <span class="shrink-0 text-xs text-slate-400">
                                                {{ $orang['jumlah'] }} tap
                                            </span>
                                        </button>
                                    @empty
                                        <div class="px-4 py-8 text-center">
                                            <p class="text-sm font-medium text-slate-700">
                                                Belum ada data tap dari mesin ini.
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Ketik PIN di bawah bila nomornya sudah diketahui.
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="pin" class="label">PIN / Employee ID</label>
                                <input
                                    id="pin"
                                    wire:model="pin"
                                    type="text"
                                    inputmode="numeric"
                                    placeholder="Contoh: 2"
                                    class="input font-mono @error('pin') input-error @enderror"
                                >
                                @error('pin')
                                    <p class="help-error">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div>
                            <label for="konfirmasi" class="label">Ketik SN mesin untuk konfirmasi</label>
                            <input
                                id="konfirmasi"
                                wire:model="konfirmasi"
                                type="text"
                                placeholder="{{ $this->device?->no_sn }}"
                                class="input font-mono @error('konfirmasi') input-error @enderror"
                                autocomplete="off"
                            >
                            @error('konfirmasi')
                                <p class="help-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4">
                            <button type="button" wire:click="resetForm" class="btn btn-secondary">
                                Reset
                            </button>
                            <button type="button" wire:click="kirim" class="btn btn-danger">
                                <x-icon name="terminal" class="h-4 w-4" />
                                Kirim ke antrean
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Riwayat --}}
                <div class="table-shell">
                    <div class="card-header">
                        <div>
                            <h3 class="section-title">Riwayat perintah</h3>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Status diperbarui dari laporan balik mesin.
                            </p>
                        </div>

                        <select wire:model.live="statusFilter" class="select h-9 w-44">
                            <option value="">Semua status</option>
                            <option value="pending">Menunggu</option>
                            <option value="sent">Dikirim</option>
                            <option value="done">Berhasil</option>
                            <option value="failed">Gagal</option>
                        </select>
                    </div>

                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Perintah</th>
                                    <th>Status</th>
                                    <th>Dikirim</th>
                                    <th>Selesai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->riwayat as $cmd)
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
                                    <tr wire:key="riwayat-{{ $cmd->id }}">
                                        <td>
                                            <span class="block font-mono text-xs text-slate-700">{{ $cmd->command }}</span>
                                            @if (filled($cmd->return_code))
                                                <span class="mt-0.5 block text-[11px] text-slate-400">Return {{ $cmd->return_code }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="text-xs text-slate-500">
                                            {{ $cmd->sent_at?->diffForHumans() ?? '—' }}
                                        </td>
                                        <td class="text-xs text-slate-500">
                                            {{ $cmd->finished_at?->diffForHumans() ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-10 text-center text-sm text-slate-500">
                                            Belum ada perintah untuk mesin ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
