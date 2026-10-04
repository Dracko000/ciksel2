<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Manajemen Pengguna</h2>
            <p class="page-subtitle">Kelola akun siswa, guru, orang tua, dan administrator.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-neutral">{{ $users->total() }} akun</span>
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
                            <option value="ortu">Orang Tua</option>
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

                <select wire:model.live="roleFilter" class="input w-full sm:w-44">
                    <option value="">Semua role</option>
                    <option value="admin">Admin</option>
                    <option value="guru">Guru</option>
                    <option value="siswa">Siswa</option>
                </select>
            </div>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>ZKTeco ID</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="font-medium text-slate-800">{{ $user->name ?? '-' }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $user->role === 'admin' ? 'badge-brand' : 'badge-neutral' }}">
                                    {{ ucfirst($user->role ?? '') }}
                                </span>
                            </td>
                            <td class="font-mono text-xs">
                                @if ($user->role === 'siswa' && $user->siswa)
                                    {{ $user->siswa->wajah_id_zkteco ?? '-' }}
                                @elseif ($user->role === 'guru' && $user->guru)
                                    {{ $user->guru->wajah_id_zkteco ?? '-' }}
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
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
                                    <button
                                        type="button"
                                        wire:click="delete({{ $user->id }})"
                                        wire:confirm="Hapus user {{ $user->name }}?"
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
                            <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                                Tidak ada user yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">{{ $users->links() }}</div>
    </div>
</div>