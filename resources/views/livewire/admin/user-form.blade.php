<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <a href="{{ route('admin.users') }}"
               class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200
                      bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:text-slate-800"
               aria-label="Kembali ke Data Pengguna">
                <x-icon name="arrow-left" class="h-4 w-4" />
            </a>

            <div>
                <h2 class="page-title">{{ $isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna' }}</h2>
                <p class="page-subtitle">
                    {{ $isEdit
                        ? 'Perbarui identitas, peran, dan ID mesin absensi pengguna.'
                        : 'Buat akun baru untuk siswa, guru/tendik, atau administrator.' }}
                </p>
            </div>
        </div>

        @if ($isEdit)
            <span class="badge badge-brand self-start sm:self-auto">Mode ubah data</span>
        @endif
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
            <h3 class="section-title">Data akun</h3>
            <span class="eyebrow">Wajib diisi</span>
        </div>

        <div class="card-body">
            <form wire:submit="simpan">
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
                        <p class="mt-1.5 text-xs text-slate-500">
                            {{ $isEdit
                                ? 'Kosongkan bila tidak ingin mengubah password.'
                                : 'Kosongkan untuk memakai username sebagai password awal.' }}
                        </p>
                        @error('password')
                            <p class="help-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="label">Peran (Role)</label>
                        <select
                            id="role"
                            wire:model.live="role"
                            class="select @error('role') input-error @enderror"
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
                            <select id="kelas_id" wire:model="kelas_id" class="select">
                                <option value="">Pilih Kelas</option>
                                @foreach ($kelasList as $kelas)
                                    <option wire:key="kelas-{{ $kelas->id }}" value="{{ $kelas->id }}">
                                        {{ $kelas->nama_kelas }}
                                    </option>
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
                                class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600/40"
                            >
                            Apakah Staff Tendik?
                        </label>
                    @endif

                    @if ($role === 'admin')
                        <div>
                            <label for="username" class="label">Username</label>
                            <input
                                id="username"
                                wire:model="username"
                                type="text"
                                class="input @error('username') input-error @enderror"
                            >
                            @error('username')
                                <p class="help-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                @if ($role === 'siswa' || $role === 'guru')
                    <div class="mt-6 border-t border-slate-100 pt-5">
                        <h4 class="section-title">Integrasi mesin absensi</h4>
                        <p class="mt-1 text-xs text-slate-500">
                            Opsional. Isi bila kartu/RFID sudah terdaftar di mesin ZKTeco.
                        </p>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="wajah_id_zkteco" class="label">ZKTeco Employee ID (Wajah/Jari)</label>
                                <input
                                    id="wajah_id_zkteco"
                                    wire:model="wajah_id_zkteco"
                                    type="text"
                                    class="input"
                                >
                                <p class="mt-1.5 text-xs text-slate-500">
                                    ID ini yang dicocokkan dengan log tap mesin absensi.
                                </p>

                                @if (! $isEdit && $rfidDariTap !== '')
                                    <p class="mt-1.5 rounded-md bg-amber-50 px-2 py-1.5 text-xs font-medium text-amber-800">
                                        Terisi dari halaman RFID Belum Terdaftar. Simpan untuk menghubungkan RFID ini.
                                    </p>
                                @endif
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
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="batal" class="btn btn-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $isEdit ? 'Simpan perubahan' : 'Simpan pengguna' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
