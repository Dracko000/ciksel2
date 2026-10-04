<div>
    <h2 class="text-2xl font-bold mb-4">Monitor Absensi Harian (ZKTeco Sync)</h2>

    <div class="mb-4 flex items-center space-x-4">
        <label class="font-semibold text-gray-700">Pilih Tanggal:</label>
        <input type="date" wire:model.live="dateFilter" class="border rounded px-3 py-2 text-gray-700 focus:outline-none focus:border-blue-500">
    </div>

    <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
        <table class="text-left w-full border-collapse">
            <thead>
                <tr>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Waktu Tap</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Device SN</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">ZKTeco ID</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Teridentifikasi Sebagai</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $log)
                    @php
                        $identitas = 'Tidak Dikenal (Unregistered)';
                        $badge = 'bg-gray-200 text-gray-800';

                        if(isset($siswas[$log->employee_id])) {
                            $identitas = $siswas[$log->employee_id]->nama . ' (Siswa)';
                            $badge = 'bg-blue-100 text-blue-800';
                        } elseif(isset($gurus[$log->employee_id])) {
                            $identitas = $gurus[$log->employee_id]->nama . ' (Guru/Tendik)';
                            $badge = 'bg-green-100 text-green-800';
                        }
                    @endphp
                <tr class="hover:bg-grey-lighter">
                    <td class="py-4 px-6 border-b border-grey-light">{{ \Carbon\Carbon::parse($log->timestamp)->format('H:i:s') }}</td>
                    <td class="py-4 px-6 border-b border-grey-light text-sm text-gray-500">{{ $log->sn }}</td>
                    <td class="py-4 px-6 border-b border-grey-light font-mono">{{ $log->employee_id }}</td>
                    <td class="py-4 px-6 border-b border-grey-light">
                        <span class="text-sm font-semibold px-2 py-1 rounded {{ $badge }}">{{ $identitas }}</span>
                    </td>
                    <td class="py-4 px-6 border-b border-grey-light">
                        <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded">Hadir</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-4 px-6 text-center text-gray-500">Belum ada data absensi pada tanggal ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $attendances->links() }}
    </div>
</div>
