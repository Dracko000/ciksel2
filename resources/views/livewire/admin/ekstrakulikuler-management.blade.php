<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Manajemen Ekstrakulikuler</h2>
            <p class="page-subtitle">Kelola kegiatan, pembina, jadwal, dan peserta siswa.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-neutral">{{ $daftar->total() }} kegiatan</span>
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

    <div class="card">
        <div class="card-header">
            <h3 class="section-title">
                {{ $isEdit ? 'Ubah Ekstrakulikuler' : 'Tambah Ekstrakulikuler' }}
            </h3>

            @if ($isEdit)
                <span class="badge badge-brand">Sedang disunting</span>
            @endif
        </div>

        <div class="card-body">
            <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="nama" class="label">Nama Kegiatan</label>
                    <input
                        id="nama"
                        type="text"
                        wire:model="nama"
                        placeholder="Contoh: Pramuka"
                        class="input @error('nama') input-error @enderror"
                    >
                    @error('nama')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="deskripsi" class="label">Deskripsi</label>
                    <textarea
                        id="deskripsi"
                        wire:model="deskripsi"
                        rows="2"
                        class="input @error('deskripsi') input-error @enderror"
                    ></textarea>
                    @error('deskripsi')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="guruSearch" class="label">Pembina</label>
                    <input
                        id="guruSearch"
                        type="search"
                        wire:model.live.debounce.300ms="guruSearch"
                        placeholder="Cari guru..."
                        class="input mb-2"
                    >
                    <select
                        wire:model="guruId"
                        class="input @error('guruId') input-error @enderror"
                    >
                        <option value="">-- Belum ditentukan --</option>
                        @foreach ($guruList as $guru)
                            <option wire:key="guru-{{ $guru->id }}" value="{{ $guru->id }}">{{ $guru->nama }}</option>
                        @endforeach
                    </select>
                    @error('guruId')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="hari" class="label">Hari</label>
                    <select
                        id="hari"
                        wire:model="hari"
                        class="input @error('hari') input-error @enderror"
                    >
                        <option value="">-- Tidak ditentukan --</option>
                        @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hariOpsi)
                            <option wire:key="hari-{{ $hariOpsi }}" value="{{ $hariOpsi }}">{{ $hariOpsi }}</option>
                        @endforeach
                    </select>
                    @error('hari')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="jamMulai" class="label">Mulai</label>
                        <input
                            id="jamMulai"
                            type="time"
                            wire:model="jamMulai"
                            class="input @error('jamMulai') input-error @enderror"
                        >
                        @error('jamMulai')
                            <p class="help-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="jamSelesai" class="label">Selesai</label>
                        <input
                            id="jamSelesai"
                            type="time"
                            wire:model="jamSelesai"
                            class="input @error('jamSelesai') input-error @enderror"
                        >
                        @error('jamSelesai')
                            <p class="help-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="lokasi" class="label">Lokasi</label>
                    <input
                        id="lokasi"
                        type="text"
                        wire:model="lokasi"
                        placeholder="Contoh: Lapangan Utama"
                        class="input @error('lokasi') input-error @enderror"
                    >
                    @error('lokasi')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="tahunAjaran" class="label">Tahun Ajaran</label>
                        <input
                            id="tahunAjaran"
                            type="text"
                            wire:model="tahunAjaran"
                            placeholder="2026/2027"
                            class="input"
                        >
                    </div>
                    <div>
                        <label for="semester" class="label">Semester</label>
                        <select
                            id="semester"
                            wire:model="semester"
                            class="input"
                        >
                            <option value="">-- Tidak ditentukan --</option>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
                    <input
                        type="checkbox"
                        wire:model="aktif"
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-600/40"
                    >
                    Aktif
                </label>

                <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $isEdit ? 'Simpan Perubahan' : 'Tambah' }}
                    </button>

                    @if ($isEdit)
                        <button type="button" wire:click="cancelEdit" class="btn btn-secondary">
                            Batal
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="table-shell">
        <div class="card-header">
            <h3 class="section-title">Daftar Ekstrakulikuler</h3>

            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <div class="relative w-full sm:w-72">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari ekstrakulikuler..."
                        class="input pl-9"
                    >
                </div>
            </div>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kegiatan</th>
                        <th>Pembina</th>
                        <th>Jadwal</th>
                        <th class="text-center">Peserta</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $item)
                        <tr wire:key="eks-{{ $item->id }}">
                            <td>
                                <span class="block font-medium text-slate-800">{{ $item->nama }}</span>
                                @if ($item->lokasi)
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $item->lokasi }}</span>
                                @endif
                            </td>
                            <td>{{ $item->guru?->nama ?? '-' }}</td>
                            <td>
                                @if ($item->hari)
                                    <span class="block">{{ $item->hari }}</span>
                                    @if ($item->jam_mulai)
                                        <span class="mt-0.5 block font-mono text-xs text-slate-500">
                                            {{ substr((string) $item->jam_mulai, 0, 5) }}@if ($item->jam_selesai)-{{ substr((string) $item->jam_selesai, 0, 5) }}@endif
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->siswa_count }}</td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    wire:click="toggleAktif({{ $item->id }})"
                                    class="btn btn-sm {{ $item->aktif ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-200' }}"
                                >
                                    {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="edit({{ $item->id }})"
                                        class="btn btn-sm btn-secondary"
                                    >
                                        <x-icon name="edit" class="h-3.5 w-3.5" />
                                        Ubah
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $item->id }})"
                                        wire:confirm="Hapus ekstrakulikuler {{ $item->nama }} beserta {{ $item->siswa_count }} peserta?"
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
                            <td colspan="6" class="py-10 text-center text-sm text-slate-500">
                                Belum ada ekstrakulikuler.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftar->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $daftar->links() }}</div>
        @endif
    </div>

    @if ($isEdit && $ekstrakulikulerId)
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card flex flex-col">
                <div class="card-header">
                    <h3 class="section-title">Tambah Peserta</h3>
                    <span class="badge badge-neutral">{{ $siswaList->count() }} tersedia</span>
                </div>

                <div class="card-body flex flex-1 flex-col">
                    <div class="relative mb-3">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="siswaSearch"
                            placeholder="Cari siswa yang belum terdaftar..."
                            class="input pl-9"
                        >
                    </div>

                    <form wire:submit.prevent="tambahPeserta" class="flex flex-1 flex-col">
                        <div class="sidebar-scroll max-h-72 flex-1 space-y-1 overflow-y-auto">
                            @forelse ($siswaList as $siswa)
                                <label
                                    wire:key="siswa-{{ $siswa->id }}"
                                    for="siswa-{{ $siswa->id }}"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition hover:bg-slate-50"
                                >
                                    <input
                                        id="siswa-{{ $siswa->id }}"
                                        type="checkbox"
                                        wire:model="terpilih"
                                        value="{{ $siswa->id }}"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-600/40"
                                    >
                                    <span class="flex-1 text-sm text-slate-700">{{ $siswa->nama }}</span>
                                    <span class="font-mono text-xs text-slate-400">{{ $siswa->nis }}</span>
                                </label>
                            @empty
                                <p class="py-6 text-center text-sm text-slate-500">
                                    Tidak ada siswa yang bisa ditambahkan.
                                </p>
                            @endforelse
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary mt-4 w-full"
                            @disabled(count($terpilih) === 0)
                        >
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambahkan {{ count($terpilih) }} siswa
                        </button>
                    </form>
                </div>
            </div>

            <div class="card flex flex-col">
                <div class="card-header">
                    <h3 class="section-title">Peserta</h3>
                    <span class="badge badge-brand">{{ $peserta?->count() ?? 0 }} siswa</span>
                </div>

                <div class="card-body">
                    <div class="divide-y divide-slate-100">
                        @forelse ($peserta as $row)
                            <div wire:key="peserta-{{ $row->id }}" class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800">{{ $row->nama }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        <span class="font-mono">{{ $row->nis }}</span>
                                        @if ($row->pivot->semester)
                                            <span>· {{ $row->pivot->semester }}</span>
                                        @endif
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="lepasPeserta({{ $row->id }})"
                                    wire:confirm="Lepas {{ $row->nama }} dari peserta?"
                                    class="btn btn-sm shrink-0 text-rose-600 hover:bg-rose-50"
                                >
                                    <x-icon name="close" class="h-3.5 w-3.5" />
                                    Lepas
                                </button>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-500">
                                Belum ada peserta untuk kegiatan ini.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>