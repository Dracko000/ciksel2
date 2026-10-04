<div>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Laporan Presensi Bulanan</h2>
            <p class="text-slate-500 font-medium">Rekapitulasi kehadiran siswa per bulan untuk SDN Cikampek Selatan 2.</p>
        </div>
        <div class="flex items-center space-x-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-200">
            <select wire:model="month" class="bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-700">
                @foreach(range(1, 12) as $m)
                    <option value="{{ sprintf('%02d', $m) }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endforeach
            </select>
            <select wire:model="year" class="bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-700">
                @foreach(range(date('Y')-2, date('Y')) as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
            <button wire:click="generateReport" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold text-xs hover:bg-indigo-700 transition">Filter</button>
        </div>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">NIS</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nama Siswa</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kelas</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Hadir</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Persentase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($reportData as $data)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-8 py-4 text-sm font-medium text-slate-500">{{ $data['nis'] }}</td>
                        <td class="px-8 py-4 text-sm font-bold text-slate-800">{{ $data['nama'] }}</td>
                        <td class="px-8 py-4 text-sm font-medium text-slate-600">{{ $data['kelas'] }}</td>
                        <td class="px-8 py-4">
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-xs font-black">
                                {{ $data['hadir'] }} Hari
                            </span>
                        </td>
                        <td class="px-8 py-4">
                            @php $pct = round(($data['hadir'] / 25) * 100); @endphp
                            <div class="w-full bg-slate-100 rounded-full h-2 max-w-[100px]">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400">{{ $pct }}%</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>
