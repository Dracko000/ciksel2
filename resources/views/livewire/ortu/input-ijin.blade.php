<div>
    <h2 class="text-2xl font-bold mb-4">Pengajuan Ijin / Sakit Siswa</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-md rounded-lg p-6">
                <form wire:submit.prevent="submit">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pilih Anak</label>
                        <select wire:model="siswa_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            @foreach($anakList as $anak)
                                <option value="{{ $anak->id }}">{{ $anak->nama }}</option>
                            @endforeach
                        </select>
                        @error('siswa_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Jenis Pengajuan</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" wire:model="jenis" value="sakit" class="form-radio text-blue-600">
                                <span class="ml-2">Sakit</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" wire:model="jenis" value="izin" class="form-radio text-blue-600">
                                <span class="ml-2">Izin</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Dari Tanggal</label>
                            <input type="date" wire:model="tanggal_mulai" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Sampai Tanggal</label>
                            <input type="date" wire:model="tanggal_selesai" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Alasan / Keterangan</label>
                        <textarea wire:model="keterangan" rows="3" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: Sakit demam, atau ada keperluan keluarga..."></textarea>
                        @error('keterangan') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded transition">
                        Kirim Pengajuan
                    </button>
                </form>
            </div>
        </div>

        <!-- Riwayat -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">Riwayat Pengajuan</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Anak</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Jenis</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayat as $item)
                                <tr>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <p class="text-gray-900 whitespace-no-wrap">{{ $item->siswa->nama }}</p>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <span class="relative inline-block px-3 py-1 font-semibold leading-tight {{ $item->jenis == 'sakit' ? 'text-red-900' : 'text-orange-900' }}">
                                            <span aria-hidden class="absolute inset-0 {{ $item->jenis == 'sakit' ? 'bg-red-200' : 'bg-orange-200' }} opacity-50 rounded-full"></span>
                                            <span class="relative">{{ ucfirst($item->jenis) }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <p class="text-gray-900 whitespace-no-wrap">{{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') }}</p>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        @if($item->status == 'pending')
                                            <span class="text-gray-500 bg-gray-100 px-2 py-1 rounded text-xs font-bold">Menunggu</span>
                                        @elseif($item->status == 'disetujui')
                                            <span class="text-green-600 bg-green-100 px-2 py-1 rounded text-xs font-bold">Disetujui</span>
                                        @else
                                            <span class="text-red-600 bg-red-100 px-2 py-1 rounded text-xs font-bold">Ditolak</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center text-gray-500">
                                        Belum ada riwayat pengajuan.
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
