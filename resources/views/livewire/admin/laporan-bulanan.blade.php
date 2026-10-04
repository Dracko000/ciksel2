<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="page-title">Laporan Presensi Bulanan</h2>
            <p class="page-subtitle">
                Rekapitulasi kehadiran siswa per bulan untuk SDN Cikampek Selatan 2.
            </p>
        </div>

        <div class="card flex flex-wrap items-end gap-3 p-4">
            <div class="w-36">
                <label for="bulan" class="label">Bulan</label>
                <select id="bulan" wire:model="month" class="input">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ sprintf('%02d', $m) }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-28">
                <label for="tahun" class="label">Tahun</label>
                <select id="tahun" wire:model="year" class="input">
                    @foreach (range(date('Y') - 2, date('Y')) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" wire:click="generateReport" class="btn btn-primary">
                <x-icon name="search" class="h-4 w-4" />
                Terapkan
            </button>
        </div>
    </div>

    <div class="table-shell">
        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Total Hadir</th>
                        <th>Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportData as $data)
                        @php
                            $pct = round(($data['hadir'] / 25) * 100);
                        @endphp
                        <tr wire:key="laporan-{{ $data['nis'] }}">
                            <td class="font-mono text-xs">{{ $data['nis'] }}</td>
                            <td class="font-medium text-slate-800">{{ $data['nama'] }}</td>
                            <td>{{ $data['kelas'] }}</td>
                            <td>
                                <span class="badge badge-brand">{{ $data['hadir'] }} Hari</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full bg-brand-600"
                                             style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium tabular-nums text-slate-500">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                                Belum ada data presensi pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>