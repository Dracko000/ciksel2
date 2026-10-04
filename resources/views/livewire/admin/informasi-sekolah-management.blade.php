<div>
    <h2 class="text-2xl font-bold mb-4">Manajemen Informasi Sekolah</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-6">
        <form wire:submit.prevent="store">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Judul Pengumuman</label>
                <input wire:model="judul" type="text" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: Libur Nasional atau Ujian Semester">
                @error('judul') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Tanggal Publikasi</label>
                <input wire:model="tanggal_publikasi" type="date" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Isi Informasi</label>
                <textarea wire:model="konten" rows="5" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Tuliskan detail pengumuman di sini..."></textarea>
                @error('konten') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-end">
                <button type="button" wire:click="resetFields" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2 transition">Batal</button>
                <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded transition">
                    {{ $isEdit ? 'Update Informasi' : 'Terbitkan Sekarang' }}
                </button>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="grid grid-cols-1 gap-4">
        @foreach($informasi as $item)
            <div class="bg-white shadow border rounded-lg p-4 hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-gray-800">{{ $item->judul }}</h3>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($item->tanggal_publikasi)->format('d F Y') }} | Oleh: {{ $item->author->name }}</p>
                    </div>
                    <div class="flex space-x-2">
                        <button wire:click="edit({{ $item->id }})" class="text-blue-500 hover:text-blue-700 text-sm font-semibold">Edit</button>
                        <button wire:click="delete({{ $item->id }})" onclick="confirm('Hapus informasi ini?') || event.stopImmediatePropagation()" class="text-red-500 hover:text-red-700 text-sm font-semibold">Hapus</button>
                    </div>
                </div>
                <div class="mt-2 text-gray-600 text-sm whitespace-pre-wrap">
                    {{ Str::limit($item->konten, 200) }}
                </div>
            </div>
        @endforeach
    </div>
</div>
