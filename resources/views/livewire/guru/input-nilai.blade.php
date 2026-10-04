<div class="space-y-6">
    <div>
        <h2 class="page-title">Input Nilai E-Raport</h2>
        <p class="page-subtitle">
            Manajemen nilai siswa untuk semester ini.
        </p>
    </div>

    @if (session()->has('message'))
        <div class="card flex items-center gap-2 border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700"
             role="alert">
            <x-icon name="check" class="h-4 w-4 shrink-0" />
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <div class="card">
                <div class="card-header">
                    <h3 class="section-title">Entry Nilai Baru</h3>
                </div>

                <div class="card-body">
                    <form wire:submit.prevent="save" class="space-y-4">
                        <div>
                            <label for="kelas_id" class="label">Pilih Kelas</label>
                            <select id="kelas_id" wire:model.live="kelas_id" class="input">
                                @foreach ($kelasList as $kelas)
                                    <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                @endforeach
                            </select>
                            @error('kelas_id') <span class="help-error">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="siswa_id" class="label">Pilih Siswa</label>
                            <select id="siswa_id" wire:model.live="siswa_id"
                                    class="input @error('siswa_id') input-error @enderror">
                                @foreach ($siswaList as $siswa)
                                    <option value="{{ $siswa->id }}">{{ $siswa->nama }}</option>
                                @endforeach
                            </select>
                            @error('siswa_id') <span class="help-error">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="mata_pelajaran" class="label">Mata Pelajaran</label>
                            <input id="mata_pelajaran" wire:model="mata_pelajaran" type="text"
                                   placeholder="Contoh: Matematika"
                                   class="input @error('mata_pelajaran') input-error @enderror">
                            @error('mata_pelajaran') <span class="help-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="nilai_angka" class="label">Nilai (0-100)</label>
                                <input id="nilai_angka" wire:model="nilai_angka" type="number"
                                       class="input @error('nilai_angka') input-error @enderror">
                                @error('nilai_angka') <span class="help-error">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="semester" class="label">Semester</label>
                                <select id="semester" wire:model.live="semester" class="input">
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            <x-icon name="check" class="h-4 w-4" />
                            Simpan Nilai
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="table-shell">
                <div class="card-header">
                    <h3 class="section-title">Daftar Nilai Siswa Terpilih</h3>
                    <span class="badge badge-neutral">{{ $existingNilai->count() }} nilai</span>
                </div>

                <div class="table-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mata Pelajaran</th>
                                <th>Nilai</th>
                                <th>Predikat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($existingNilai as $n)
                                @php
                                    if ($n->nilai_angka >= 85) {
                                        $predikat = 'A';
                                        $badge = 'badge-success';
                                    } elseif ($n->nilai_angka >= 75) {
                                        $predikat = 'B';
                                        $badge = 'badge-brand';
                                    } elseif ($n->nilai_angka >= 60) {
                                        $predikat = 'C';
                                        $badge = 'badge-warning';
                                    } else {
                                        $predikat = 'E';
                                        $badge = 'badge-danger';
                                    }
                                @endphp
                                <tr wire:key="nilai-{{ $n->id }}">
                                    <td class="font-medium text-slate-800">{{ $n->mata_pelajaran }}</td>
                                    <td>
                                        <span class="text-base font-semibold tabular-nums text-slate-800">
                                            {{ $n->nilai_angka }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badge }}">{{ $predikat }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-10 text-center text-sm text-slate-500">
                                        Belum ada nilai untuk siswa ini.
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