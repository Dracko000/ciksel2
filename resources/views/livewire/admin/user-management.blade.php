<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Manajemen Pengguna</h2>
            <p class="page-subtitle">Kelola akun siswa, guru, dan administrator sekolah.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-neutral">{{ $users->total() }} akun</span>
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
                {{ $isEdit ? 'Update Pengguna' : 'Tambah Pengguna' }}
            </h3>

            @if ($isEdit)
                <span class="badge badge-brand">Sedang disunting</span>
            @endif
        </div>

        <div class="card-body">
            <form wire:submit.prevent="store">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="name" class="label">Nama</label>
                        <input
                            id="name"
                            wire:model="name"
                            type="text"
                            required
                            class="input @error('name') input-error @enderror"
                        >
                        @error('name')
                            <p class="help-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="label">Email</label>
                        <input
                            id="email"
                            wire:model="email"
                            type="email"
                            required
                            class="input @error('email') input-error @enderror"
                        >
                        @error('email')
                            <p class="help-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <input
                            id="password"
                            wire:model="password"
                            type="password"
                            class="input @error('password') input-error @enderror"
                        >
                        <p class="mt-1.5 text-xs text-slate-500">Kosongkan bila tidak ingin mengubah.</p>
                        @error('password')
                            <p class="help-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="label">Peran (Role)</label>
                        <select
                            id="role"
                            wire:model.live="role"
                            class="input @error('role') input-error @enderror"
                        >
                            <option value="siswa">Siswa</option>
                            <option value="guru">Guru / Tendik</option>
                            <option value="admin">Admin</option>
                        </select>
                        @error('role')
                            <p class="help-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($role === 'siswa')
                        <div>
                            <label for="nis" class="label">NIS</label>
                            <input
                                id="nis"
                                wire:model="nis"
                                type="text"
                                class="input @error('nis') input-error @enderror"
                            >
                            @error('nis')
                                <p class="help-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="kelas_id" class="label">Kelas</label>
                            <select id="kelas_id" wire:model="kelas_id" class="input">
                                <option value="">Pilih Kelas</option>
                                @foreach ($kelasList as $kelas)
                                    <option wire:key="kelas-{{ $kelas->id }}" value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($role === 'guru')
                        <div>
                            <label for="nip" class="label">NIP / NUPTK</label>
                            <input
                                id="nip"
                                wire:model="nip"
                                type="text"
                                class="input @error('nip') input-error @enderror"
                            >
                            @error('nip')
                                <p class="help-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
                            <input
                                wire:model="is_tendik"
                                type="checkbox"
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-600/40"
                            >
                            Apakah Staff Tendik?
                        </label>
                    @endif

                    @if ($role === 'siswa' || $role === 'guru')
                        <div>
                            <label for="wajah_id_zkteco" class="label">ZKTeco Employee ID (Wajah/Jari)</label>
                            <input
                                id="wajah_id_zkteco"
                                wire:model="wajah_id_zkteco"
                                type="text"
                                class="input"
                            >
                            <p class="mt-1.5 text-xs text-slate-500">
                                ID ini akan disinkronkan dengan log dari mesin absensi ZKTeco.
                            </p>
                        </div>

                        <div>
                            <label for="rfid_kartu" class="label">RFID Kartu</label>
                            <input
                                id="rfid_kartu"
                                wire:model="rfid_kartu"
                                type="text"
                                class="input"
                            >
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="resetFields" class="btn btn-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $isEdit ? 'Update' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-shell">
        <div class="card-header">
            <h3 class="section-title">Daftar Pengguna</h3>

            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <div class="relative w-full sm:w-80">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama, NIS/NIP, atau email..."
                        class="input pl-9"
                    >
                </div>

                <select wire:model.live="roleFilter" class="select w-full sm:w-44" aria-label="Filter role">
                    <option value="">Semua role</option>
                    <option value="admin">Admin</option>
                    <option value="guru">Guru</option>
                    <option value="siswa">Siswa</option>
                </select>

                <select wire:model.live="kelasFilter" class="select w-full sm:w-44" aria-label="Filter kelas">
                    <option value="">Semua kelas</option>
                    @foreach ($kelasList as $kelasFilterItem)
                        <option value="{{ $kelasFilterItem->id }}">{{ $kelasFilterItem->nama_kelas }}</option>
                    @endforeach
                </select>

                <select wire:model.live="perPage" class="select w-full sm:w-28" aria-label="Jumlah baris per halaman">
                    <option value="25">25 baris</option>
                    <option value="50">50 baris</option>
                    <option value="100">100 baris</option>
                </select>
            </div>
        </div>

        {{-- Bilah aksi massal: hanya muncul saat ada baris terpilih --}}
        @if (count($selected) > 0)
            <div class="bulk-bar" role="region" aria-label="Aksi massal">
                <span class="text-sm font-medium text-brand-700">
                    <span class="num">{{ count($selected) }}</span> akun terpilih
                </span>

                <button type="button" wire:click="askBulkDelete" class="btn btn-sm btn-danger">
                    <x-icon name="trash" class="h-3.5 w-3.5" />
                    Hapus terpilih
                </button>

                <button type="button" wire:click="$set('selected', [])" class="btn btn-sm btn-ghost">
                    Batalkan pilihan
                </button>
            </div>
        @endif

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input
                                type="checkbox"
                                wire:click="toggleSelectAll"
                                @checked($this->allVisibleSelected($users->count()))
                                class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600/40"
                                aria-label="Pilih semua akun di halaman ini"
                            >
                        </th>
                        <th>NIK / NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Role</th>
                        <th>RFID</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $isSiswa = $user->role === 'siswa' && $user->siswa;
                            $isGuru = $user->role === 'guru' && $user->guru;
                            $kelas = $isSiswa ? $user->siswa->kelas?->nama_kelas : null;
                            $identitas = $isSiswa ? $user->siswa->nis : ($isGuru ? $user->guru->nip : $user->username);
                        @endphp
                        <tr wire:key="user-{{ $user->id }}"
                            @class(['row-selected' => $this->isSelected($user->id)])>
                            <td>
                                <input
                                    type="checkbox"
                                    wire:model.live="selected"
                                    value="{{ $user->id }}"
                                    @checked($this->isSelected($user->id))
                                    class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600/40"
                                    aria-label="Pilih {{ $user->name }}"
                                >
                            </td>
                            <td class="num font-medium text-maroon-100">{{ $identitas ?? '-' }}</td>
                            <td class="font-medium text-maroon-50">{{ $user->name ?? '-' }}</td>
                            <td>{{ $kelas ?? '-' }}</td>
                            <td>
                                @php
                                    // Tiga peran diberi warna berbeda supaya kolom ini
                                    // bisa dibaca sekilas tanpa membaca teksnya.
                                    // "danger" dipakai untuk status merusak (Ditolak,
                                    // Offline), bukan untuk peran.
                                    $roleBadge = match ($user->role) {
                                        'admin' => 'badge-brand',
                                        'guru' => 'badge-info',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $roleBadge }}">
                                    {{ ucfirst($user->role ?? '') }}
                                </span>
                            </td>
                            <td class="num text-xs text-maroon-200">
                                {{ $isSiswa ? ($user->siswa->rfid_kartu ?: '-') : ($isGuru ? ($user->guru->rfid_kartu ?: '-') : '-') }}
                            </td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="edit({{ $user->id }})"
                                        class="btn btn-sm btn-secondary"
                                    >
                                        <x-icon name="edit" class="h-3.5 w-3.5" />
                                        Edit
                                    </button>
                                    @if ((int) $user->id !== (int) auth()->id())
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $user->id }})"
                                            class="btn btn-sm btn-danger"
                                            aria-label="Hapus akun {{ $user->name }}"
                                        >
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <x-icon name="users" class="h-10 w-10 text-maroon-300" />
                                    <p class="empty-state-title">Tidak ada akun yang cocok</p>
                                    <p class="empty-state-text">
                                        Ubah kata kunci pencarian, filter role, atau filter kelas,
                                        atau tambahkan akun baru lewat formulir di atas.
                                    </p>
                                    <button
                                        type="button"
                                        wire:click="resetFilter"
                                        class="btn btn-secondary mt-4"
                                    >
                                        Bersihkan pencarian &amp; filter
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-maroon-200">
                Menampilkan
                <span class="num font-medium text-maroon-100">{{ $users->firstItem() ?? 0 }}</span>&ndash;<span
                    class="num font-medium text-maroon-100">{{ $users->lastItem() ?? 0 }}</span>
                dari <span class="num font-medium text-maroon-100">{{ $users->total() }}</span> akun
            </p>
            <div>{{ $users->links() }}</div>
        </div>
    </div>

    {{-- Modal konfirmasi hapus: wajib mengetik ulang nama entitas --}}
    <x-confirm-delete-modal
        :open="$deleteMode !== null"
        :title="$deleteMode === 'bulk' ? 'Hapus akun terpilih?' : 'Hapus akun?'"
        :target-name="$deleteTargetName"
        :confirm-value="$deleteConfirmText"
        input-name="deleteConfirmText"
        error-key="deleteConfirmText"
        confirm-method="confirmDelete"
        cancel-method="cancelDelete"
        :impact="$deleteMode === 'bulk'
            ? count($selected) . ' akun akan dihapus permanen beserta seluruh riwayat absensinya. Tindakan ini tidak dapat dibatalkan.'
            : 'Akun ' . $deleteTargetName . ' akan dihapus permanen beserta seluruh riwayat absensinya. Tindakan ini tidak dapat dibatalkan.'"
    />
</div>