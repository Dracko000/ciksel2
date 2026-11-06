<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Jam Guru</h2>
            <p class="page-subtitle">
                Atur jam masuk dan jam pulang tiap guru &amp; tenaga kependidikan.
            </p>
        </div>

        <span class="badge badge-neutral">{{ count($gurus) }} orang</span>
    </div>

    <x-flash-toast />

    <div class="card">
        <div class="card-header">
            <h3 class="section-title">Jam Masuk / Pulang per Orang</h3>
        </div>

        <div class="card-body">
            @if ($gurus->isEmpty())
                <p class="text-sm text-slate-500">
                    Belum ada guru atau tenaga kependidikan yang terdaftar.
                </p>
            @else
                <form wire:submit.prevent="save" class="space-y-4">
                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>NIP</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($gurus as $guru)
                                    <tr wire:key="guru-{{ $guru->id }}">
                                        <td class="font-medium text-slate-900">
                                            {{ $guru->nama }}
                                            @if ($guru->is_tendik)
                                                <span class="badge badge-neutral ml-1">Tendik</span>
                                            @endif
                                        </td>
                                        <td class="text-slate-500">{{ $guru->nip ?? '-' }}</td>
                                        <td>
                                            <input
                                                type="time"
                                                wire:model="jadwal.{{ $guru->id }}.masuk"
                                                class="input @error("jadwal.{$guru->id}.masuk") input-error @enderror"
                                            >
                                            @error("jadwal.{$guru->id}.masuk")
                                                <p class="help-error">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td>
                                            <input
                                                type="time"
                                                wire:model="jadwal.{{ $guru->id }}.pulang"
                                                class="input @error("jadwal.{$guru->id}.pulang") input-error @enderror"
                                            >
                                            @error("jadwal.{$guru->id}.pulang")
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
                            Simpan Jam Guru
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <p class="text-xs text-slate-500">
        Jam ini menjadi acuan keterlambatan tiap guru secara perorangan. Biarkan
        kosong bila guru tidak perlu dinilai keterlambatannya &mdash; wali kelas
        yang belum diatur tetap mengikuti jam kelas yang diampu.
    </p>
</div>
