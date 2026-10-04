<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="page-title">Monitor Absensi Harian</h2>
            <p class="page-subtitle">
                Rekapitulasi tap mesin ZKTeco berdasarkan tanggal yang dipilih.
            </p>
        </div>

        <div class="w-full sm:w-64">
            <label for="dateFilter" class="label">Pilih Tanggal</label>
            <input id="dateFilter" type="date" wire:model.live="dateFilter" class="input">
        </div>
    </div>

    <div class="table-shell">
        <div class="card-header">
            <h3 class="section-title">Log Absensi Mesin</h3>
            <span class="badge badge-neutral">{{ $attendances->total() }} catatan</span>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu Tap</th>
                        <th>Device SN</th>
                        <th>ZKTeco ID</th>
                        <th>Teridentifikasi Sebagai</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $log)
                        @php
                            $identitas = 'Tidak Dikenal (Unregistered)';
                            $badge = 'badge-neutral';

                            if (isset($siswas[$log->employee_id])) {
                                $identitas = $siswas[$log->employee_id]->nama . ' (Siswa)';
                                $badge = 'badge-brand';
                            } elseif (isset($gurus[$log->employee_id])) {
                                $identitas = $gurus[$log->employee_id]->nama . ' (Guru/Tendik)';
                                $badge = 'badge-success';
                            }
                        @endphp
                        <tr wire:key="absensi-{{ $log->id }}">
                            <td class="whitespace-nowrap font-mono text-xs">
                                {{ \Carbon\Carbon::parse($log->timestamp)->format('H:i:s') }}
                            </td>
                            <td class="font-mono text-xs">{{ $log->sn }}</td>
                            <td class="font-mono text-xs font-medium text-brand-700">{{ $log->employee_id }}</td>
                            <td>
                                <span class="badge {{ $badge }}">{{ $identitas }}</span>
                            </td>
                            <td>
                                <span class="badge badge-success">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Hadir
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                                Belum ada data absensi pada tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($attendances->hasPages())
        <div class="mt-4">
            {{ $attendances->links() }}
        </div>
    @endif
</div>