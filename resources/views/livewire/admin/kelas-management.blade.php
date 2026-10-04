<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Manajemen Kelas</h2>
            <p class="page-subtitle">Kelola rombel, wali kelas, dan perpindahan siswa.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-neutral">{{ $kelasList->total() }} kelas</span>
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
                {{ $isEdit ? 'Ubah Kelas' : 'Tambah Kelas' }}
            </h3>

            @if ($isEdit)
                <span class="badge badge-brand">Sedang disunting</span>
            @endif
        </div>

        <div class="card-body">
            <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="namaKelas" class="label">Nama Kelas</label>
                    <input
                        id="namaKelas"
                        type="text"
                        wire:model="namaKelas"
                        placeholder="Contoh: 1 A"
                        class="input @error('namaKelas') input-error @enderror"
                    >
                    @error('namaKelas')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="waliKelasId" class="label">Wali Kelas</label>
                    <input
                        id="waliSearch"
                        type="search"
                        wire:model.live.debounce.300ms="waliSearch"
                        placeholder="Cari wali kelas..."
                        class="input mb-2"
                    >
                    <select
                        id="waliKelasId"
                        wire:model="waliKelasId"
                        class="input @error('waliKelasId') input-error @enderror"
                    >
                        <option value="">-- Belum ditentukan --</option>
                        @foreach ($waliList as $wali)
                            <option wire:key="wali-{{ $wali->id }}" value="{{ $wali->id }}">
                                {{ $wali->nama }}{{ $wali->is_tendik ? ' (tendik)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('waliKelasId')
                        <p class="help-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Kelas' }}
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
            <h3 class="section-title">Daftar Kelas</h3>

            <div class="relative w-full sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari kelas..."
                    class="input pl-9"
                >
            </div>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Wali Kelas</th>
                        <th class="text-center">Siswa</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kelasList as $kelas)
                        <tr wire:key="kelas-{{ $kelas->id }}">
                            <td class="font-medium text-slate-800">{{ $kelas->nama_kelas }}</td>
                            <td>{{ $kelas->waliKelas?->nama ?? 'Belum ditentukan' }}</td>
                            <td class="text-center">{{ $kelas->siswa_count }}</td>
                            <td>
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="showRoster({{ $kelas->id }})"
                                        class="btn btn-sm btn-secondary"
                                    >
                                        <x-icon name="users" class="h-3.5 w-3.5" />
                                        {{ $showRosterFor === $kelas->id ? 'Tutup Roster' : 'Roster' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="edit({{ $kelas->id }})"
                                        class="btn btn-sm btn-secondary"
                                    >
                                        <x-icon name="edit" class="h-3.5 w-3.5" />
                                        Ubah
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $kelas->id }})"
                                        wire:confirm="Hapus kelas {{ $kelas->nama_kelas }}? {{ $kelas->siswa_count }} siswa akan kehilangan kelas."
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
                                Belum ada kelas. Tambahkan lewat form di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($kelasList->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $kelasList->links() }}</div>
        @endif
    </div>

    @if ($roster)
        <div class="table-shell">
            <div class="card-header">
                <h3 class="section-title">
                    Roster Siswa
                    <span class="ml-1 font-normal normal-case tracking-normal text-slate-400">
                        ({{ $roster->total() }} siswa)
                    </span>
                </h3>

                <div class="relative w-full sm:w-64">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="rosterSearch"
                        placeholder="Cari siswa..."
                        class="input pl-9"
                    >
                </div>
            </div>

            <div class="table-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>Username</th>
                            <th class="text-right">Pindah Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roster as $index => $row)
                            <tr wire:key="roster-{{ $row->id }}">
                                <td class="text-slate-400">{{ $roster->firstItem() + $index }}</td>
                                <td class="font-medium text-slate-800">{{ $row->nama }}</td>
                                <td class="font-mono text-xs">{{ $row->nis }}</td>
                                <td>{{ $row->user?->username ?? '-' }}</td>
                                <td>
                                    <div class="flex justify-end">
                                        <select
                                            wire:change="pindahSiswa({{ $row->id }}, $event.target.value)"
                                            aria-label="Pindah kelas {{ $row->nama }}"
                                            class="input py-1.5 text-xs"
                                        >
                                            @foreach ($kelasOpsi as $id => $nama)
                                                <option wire:key="opsi-{{ $id }}" value="{{ $id }}" @selected($id === $row->kelas_id)>{{ $nama }}</option>
                                            @endforeach
                                            <option value="" @selected($row->kelas_id === null)>-- Tanpa Kelas --</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                                    Kelas ini belum punya siswa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($roster->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $roster->links() }}</div>
            @endif
        </div>
    @endif
</div>