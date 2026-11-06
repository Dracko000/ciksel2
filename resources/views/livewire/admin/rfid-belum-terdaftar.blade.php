<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">RFID Belum Terdaftar</h2>
            <p class="page-subtitle">
                Tap dari mesin absensi yang RFID/ID-nya belum cocok dengan data siswa maupun guru.
                Hubungkan ke pemiliknya supaya tap berikutnya ikut masuk rekap presensi.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-warning">{{ count($taps) }} RFID</span>
            <span class="badge badge-neutral">{{ number_format($totalTap, 0, ',', '.') }} tap</span>
        </div>
    </div>

    <x-flash-toast />

    <div class="card border-sky-100 bg-sky-50 p-4">
        <div class="flex items-start gap-3">
            <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-sky-600" />

            <div class="min-w-0 text-sm text-sky-900">
                <p class="font-medium">Cocokkan sekali, berlaku seterusnya.</p>
                <p class="mt-1 text-xs text-sky-800">
                    ID mesin disimpan sebagai ID ZKTeco milik pengguna. Tap lama pada tanggal yang
                    dibuka di halaman Presensi ikut dikenali, jadi tidak perlu mengetik ulang.
                    Belum ada orangnya? <a href="{{ route('admin.users.create') }}" class="font-semibold underline decoration-sky-400 underline-offset-2 hover:text-sky-950">Buat pengguna baru</a>.
                </p>
            </div>
        </div>
    </div>

    <div class="table-shell">
        <div class="card-header">
            <div>
                <h3 class="section-title">Daftar RFID Asing</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Diurutkan dari tap terakhir. Data diambil dari log mentah mesin, bukan dari rekap.
                </p>
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari RFID..."
                        class="input pl-9"
                        aria-label="Cari RFID"
                    >
                </div>

                <select wire:model.live="rentang" class="select w-full sm:w-40" aria-label="Rentang waktu">
                    <option value="all">Semua riwayat</option>
                    <option value="7">7 hari terakhir</option>
                    <option value="30">30 hari terakhir</option>
                    <option value="90">90 hari terakhir</option>
                </select>

                <select wire:model.live="mesinFilter" class="select w-full sm:w-44" aria-label="Filter mesin">
                    <option value="">Semua mesin</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->no_sn }}">{{ $device->nama ?: $device->no_sn }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-14">No</th>
                        <th>RFID (dari mesin)</th>
                        <th>Mesin</th>
                        <th>Jumlah Tap</th>
                        <th>Pertama Terlihat</th>
                        <th>Terakhir Terlihat</th>
                        <th>Biasa Masuk</th>
                        <th>Biasa Pulang</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($taps as $tap)
                        <tr wire:key="rfid-asing-{{ $tap['employee_id'] }}">
                            <td class="num text-xs text-slate-400">{{ $loop->iteration }}</td>
                            <td class="num text-xs font-semibold text-slate-800">{{ $tap['employee_id'] }}</td>
                            <td>
                                @if (! empty($tap['mesin']))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($tap['mesin'] as $mesin)
                                            <span class="badge badge-neutral" title="SN {{ $mesin['sn'] }}">
                                                <x-icon name="device" class="h-3 w-3" />
                                                {{ $mesin['nama'] }}
                                                <span class="num text-slate-400">({{ $mesin['jumlah'] }})</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">&mdash;</span>
                                @endif
                            </td>
                            <td class="num text-xs text-slate-600">{{ $tap['jumlahTap'] }}</td>
                            <td class="num text-xs text-slate-500">{{ $tap['pertama'] }}</td>
                            <td class="num text-xs text-slate-500">{{ $tap['terakhir'] }}</td>
                            <td class="num text-xs font-medium text-slate-700">{{ $tap['jamMasuk'] ?? '—' }}</td>
                            <td class="num text-xs font-medium text-slate-700">{{ $tap['jamPulang'] ?? '—' }}</td>
                            <td class="text-right">
                                <button
                                    type="button"
                                    wire:click="bukaAssign('{{ $tap['employee_id'] }}')"
                                    class="btn btn-sm btn-primary"
                                >
                                    <x-icon name="user" class="h-3.5 w-3.5" />
                                    Hubungkan
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-sm text-slate-500">
                                @if ($search !== '')
                                    Tidak ada RFID yang cocok dengan pencarian "{{ $search }}".
                                @elseif ($mesinFilter !== '')
                                    Tidak ada RFID belum terdaftar dari mesin ini pada rentang yang dipilih.
                                @else
                                    Semua tap pada rentang ini sudah cocok dengan data siswa/guru.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
            RFID di atas tidak masuk donut/status presensi selama belum punya pemilik. Setelah
            dihubungkan, tap orang tersebut langsung dihitung pada tanggal yang dibuka.
        </p>
    </div>

    @if ($assigningRfid !== '')
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="hubungkan-rfid-judul"
            x-data
            x-on:keydown.escape.window="$wire.tutupAssign()"
            wire:click="tutupAssign"
        >
            <div class="w-full max-w-2xl rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10" wire:click.stop>
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                            <x-icon name="user" class="h-5 w-5" />
                        </span>

                        <div class="min-w-0">
                            <h3 id="hubungkan-rfid-judul" class="text-base font-semibold text-slate-900">
                                Hubungkan RFID ke pengguna
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Pilih siswa/guru yang memegang kartu atau terdaftar dengan ID mesin ini.
                            </p>
                            <p class="num mt-2 inline-flex rounded-md bg-slate-100 px-2 py-1 text-sm font-semibold tracking-wide text-slate-800">
                                {{ $assigningRfid }}
                            </p>
                        </div>
                    </div>

                    <button type="button" wire:click="tutupAssign"
                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            aria-label="Tutup">
                        <x-icon name="close" class="h-5 w-5" />
                    </button>
                </div>

                <div class="p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                type="search"
                                wire:model.live.debounce.300ms="userSearch"
                                placeholder="Cari nama, NIS/NIP, atau username..."
                                class="input pl-9"
                                aria-label="Cari pengguna"
                            >
                        </div>

                        <label class="flex shrink-0 items-center gap-2 text-xs text-slate-600">
                            <input
                                type="checkbox"
                                wire:model.live="hanyaTanpaId"
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-600/40"
                            >
                            Hanya yang belum punya ID mesin
                        </label>
                    </div>

                    @error('assigningRfid')
                        <p class="mt-2 text-xs font-medium text-bahaya-700">{{ $message }}</p>
                    @enderror

                    <div class="mt-3 max-h-[46vh] divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                        @forelse ($pengguna as $user)
                            @php
                                $isSiswa = $user->role === 'siswa';
                                $identitas = $isSiswa ? $user->siswa?->nis : $user->guru?->nip;
                                $idMesin = $isSiswa ? $user->siswa?->wajah_id_zkteco : $user->guru?->wajah_id_zkteco;
                            @endphp

                            <label
                                wire:key="pilih-user-{{ $user->id }}"
                                class="flex cursor-pointer items-start gap-3 p-3 transition hover:bg-slate-50
                                       {{ (string) $selectedUserId === (string) $user->id ? 'bg-brand-50/60' : '' }}"
                            >
                                <input
                                    type="radio"
                                    wire:model.live="selectedUserId"
                                    value="{{ $user->id }}"
                                    class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-600/40"
                                >

                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</span>
                                        <span class="badge badge-neutral">
                                            {{ $isSiswa ? 'Siswa' : ($user->guru?->is_tendik ? 'Tendik' : 'Guru') }}
                                        </span>
                                        @if (filled($idMesin))
                                            <span class="badge badge-warning">ID lama: {{ $idMesin }}</span>
                                        @endif
                                    </span>

                                    <span class="mt-0.5 block text-xs text-slate-500">
                                        {{ $isSiswa ? 'NIS' : 'NIP' }} {{ $identitas ?: '—' }}
                                        @if ($isSiswa)
                                            &middot; Kelas {{ $user->siswa?->kelas?->nama_kelas ?? '—' }}
                                        @else
                                            &middot; {{ $user->guru?->is_tendik ? 'Tenaga kependidikan' : 'Guru' }}
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @empty
                            <div class="p-8 text-center">
                                <p class="text-sm font-medium text-slate-700">
                                    Tidak ada pengguna yang cocok.
                                </p>
                                <p class="mx-auto mt-1 max-w-sm text-xs text-slate-500">
                                    Coba matikan centang "Hanya yang belum punya ID mesin", atau
                                    daftarkan orangnya lebih dulu di Data Pengguna.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    @if ($pengguna->count() >= 50)
                        <p class="mt-2 text-xs text-slate-500">
                            Menampilkan 50 hasil pertama. Persempit pencarian untuk melihat pengguna lain.
                        </p>
                    @endif

                    @error('selectedUserId')
                        <p class="mt-2 text-xs font-medium text-bahaya-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 p-5">
                    <a href="{{ route('admin.users.create', ['rfid' => $assigningRfid]) }}"
                       target="_blank" rel="noopener"
                       class="btn btn-secondary btn-sm">
                        <x-icon name="plus" class="h-3.5 w-3.5" />
                        Buat pengguna baru
                    </a>

                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="tutupAssign" class="btn btn-secondary">
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="hubungkan"
                            class="btn btn-primary"
                            @disabled(! $selectedUserId)
                        >
                            <x-icon name="check" class="h-4 w-4" />
                            Hubungkan RFID
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
