<div>
    <h2 class="text-2xl font-bold mb-4">Konfirmasi Ijin / Sakit Siswa</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
        <table class="text-left w-full border-collapse">
            <thead>
                <tr>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Siswa</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Jenis</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Keterangan</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Tanggal</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Status</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengajuan as $item)
                <tr class="hover:bg-grey-lighter">
                    <td class="py-4 px-6 border-b border-grey-light">{{ $item->siswa->nama }}</td>
                    <td class="py-4 px-6 border-b border-grey-light">
                        <span class="px-2 py-1 rounded text-xs font-bold {{ $item->jenis == 'sakit' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($item->jenis) }}
                        </span>
                    </td>
                    <td class="py-4 px-6 border-b border-grey-light text-sm">{{ $item->keterangan }}</td>
                    <td class="py-4 px-6 border-b border-grey-light text-xs">
                        {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') }}
                    </td>
                    <td class="py-4 px-6 border-b border-grey-light">
                        @if($item->status == 'pending')
                            <span class="text-gray-500 font-bold">Pending</span>
                        @elseif($item->status == 'disetujui')
                            <span class="text-green-600 font-bold">Disetujui</span>
                        @else
                            <span class="text-red-600 font-bold">Ditolak</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 border-b border-grey-light">
                        @if($item->status == 'pending')
                            <button wire:click="approve({{ $item->id }})" class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs">Setuju</button>
                            <button wire:click="reject({{ $item->id }})" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">Tolak</button>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-4 px-6 text-center text-gray-500">Belum ada pengajuan ijin.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
