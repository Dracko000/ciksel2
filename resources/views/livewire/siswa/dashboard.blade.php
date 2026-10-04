<div class="space-y-6">
    <div>
        <h1 class="page-title">Halo, {{ auth()->user()->name }}</h1>
        <p class="page-subtitle">Ringkasan kehadiran dan informasi kelas kamu.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-card title="Kelas">
            <x-slot name="icon"><x-icon name="classroom" class="h-5 w-5" /></x-slot>
            <p class="text-2xl font-semibold tracking-tight">{{ $kelas?->nama_kelas ?? 'Belum diatur' }}</p>
        </x-card>

        <x-card title="NIS">
            <x-slot name="icon"><x-icon name="user" class="h-5 w-5" /></x-slot>
            <p class="text-2xl font-semibold tracking-tight">{{ $siswa?->nis ?? '-' }}</p>
        </x-card>

        <x-card title="Kehadiran Tercatat" icon-tone="success">
            <x-slot name="icon"><x-icon name="check" class="h-5 w-5" /></x-slot>
            <p class="text-2xl font-semibold tracking-tight">{{ $hadir }}</p>
            <p class="mt-1 text-xs text-slate-500">dari {{ $total }} log presensi</p>
        </x-card>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Presensi Terakhir</h2>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Mesin</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($terakhir as $log)
                        <tr wire:key="hadir-{{ $log->id }}">
                            <td class="whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($log->timestamp)->translatedFormat('d M Y H:i') }} WIB
                            </td>
                            <td class="font-mono text-xs">{{ $log->sn }}</td>
                            <td>
                                <span @class([
                                    'badge badge-success' => (int) $log->status1 !== 0,
                                    'badge badge-danger' => (int) $log->status1 === 0,
                                ])>{{ (int) $log->status1 !== 0 ? 'Hadir' : 'Tidak hadir' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-10 text-center text-sm text-slate-500">
                                Belum ada data presensi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>