<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Jam Kelas</h2>
            <p class="page-subtitle">
                Atur jam masuk dan jam pulang tiap tingkat kelas.
            </p>
        </div>

        <span class="badge badge-neutral">{{ count($daftarTingkat) }} tingkat</span>
    </div>

    <x-flash-toast />

    <div class="card">
        <div class="card-header">
            <h3 class="section-title">Jam Masuk / Pulang per Tingkat</h3>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save" class="space-y-4">
                <div class="table-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tingkat</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarTingkat as $tingkat)
                                <tr wire:key="tingkat-{{ $tingkat }}">
                                    <td class="font-medium text-slate-900">Kelas {{ $tingkat }}</td>
                                    <td>
                                        <input
                                            type="time"
                                            wire:model="jadwal.{{ $tingkat }}.masuk"
                                            class="input @error("jadwal.{$tingkat}.masuk") input-error @enderror"
                                        >
                                        @error("jadwal.{$tingkat}.masuk")
                                            <p class="help-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td>
                                        <input
                                            type="time"
                                            wire:model="jadwal.{{ $tingkat }}.pulang"
                                            class="input @error("jadwal.{$tingkat}.pulang") input-error @enderror"
                                        >
                                        @error("jadwal.{$tingkat}.pulang")
                                            <p class="help-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        Simpan Jam Kelas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <p class="text-xs text-slate-500">
        Jam masuk dipakai untuk menilai siswa terlambat, dan titik tengah jam
        masuk&ndash;pulang dipakai menebak tap masuk atau pulang dari mesin.
    </p>
</div>
