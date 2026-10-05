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
                            <td class="font-medium text-maroon-50">{{ $kelas->nama_kelas }}</td>
                            <td>
                                @if ($kelas->waliKelas)
                                    {{ $kelas->waliKelas->nama }}
                                @else
                                    <span class="badge badge-warning">Belum ditentukan</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $kelas->siswa_count > 0 ? 'badge-info' : 'badge-neutral' }}">
                                    {{ $kelas->siswa_count }}
                                </span>
                            </td>
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
                                        wire:click="askDelete({{ $kelas->id }})"
                                        class="btn btn-sm btn-danger"
                                        aria-label="Hapus kelas {{ $kelas->nama_kelas }}"
                                    >
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-sm text-maroon-200">
                                Belum ada kelas. Tambahkan lewat form di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($kelasList->hasPages())
            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-maroon-200">Menampilkan <span class="num font-medium text-maroon-100">{{ $kelasList->firstItem() ?? 0 }}</span>&ndash;<span class="num font-medium text-maroon-100">{{ $kelasList->lastItem() ?? 0 }}</span> dari <span class="num font-medium text-maroon-100">{{ $kelasList->total() }}</span> kelas</p>
                <div>{{ $kelasList->links() }}</div>
            </div>
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
                                <td class="text-maroon-300">{{ $roster->firstItem() + $index }}</td>
                                <td class="font-medium text-maroon-50">{{ $row->nama }}</td>
                                <td class="num text-xs">{{ $row->nis }}</td>
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
                                <td colspan="5" class="py-10 text-center text-sm text-maroon-200">
                                    Kelas ini belum punya siswa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($roster->hasPages())
                <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-maroon-200">Menampilkan <span class="num font-medium text-maroon-100">{{ $roster->firstItem() ?? 0 }}</span>&ndash;<span class="num font-medium text-maroon-100">{{ $roster->lastItem() ?? 0 }}</span> dari <span class="num font-medium text-maroon-100">{{ $roster->total() }}</span> siswa</p>
                    <div>{{ $roster->links() }}</div>
                </div>
            @endif
        </div>
    @endif

    {{-- Hapus kelas: admin wajib mengetik ulang nama kelas --}}
    <x-confirm-delete-modal
        :open="$hapusKelasId !== null"
        title="Hapus kelas?"
        :target-name="$hapusKelasNama"
        :confirm-value="$hapusKelasKonfirmasi"
        input-name="hapusKelasKonfirmasi"
        error-key="hapusKelasKonfirmasi"
        confirm-method="confirmHapusKelas"
        cancel-method="cancelHapusKelas"
        :impact="$hapusKelasJumlahSiswa > 0
            ? $hapusKelasJumlahSiswa . ' siswa di dalam kelas ini akan kehilangan kelasnya dan harus dipindahkan secara manual. Tindakan ini tidak dapat dibatalkan.'
            : 'Kelas akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.'"
    />
</div>