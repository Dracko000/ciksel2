<div>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Monitoring Device ZKTeco</h2>
            <p class="text-slate-500 font-medium">Pantau status koneksi dan kelola identitas mesin absensi Anda.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-2xl relative mb-6 font-bold" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Form Edit -->
        <div class="lg:col-span-1">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h3 class="text-xl font-black text-slate-800 mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                    {{ $isEdit ? 'Edit Device' : 'Tambah Device Manual' }}
                </h3>
                
                <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">Serial Number (SN)</label>
                        <input wire:model="no_sn" type="text" placeholder="Masukkan SN Mesin" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 transition font-medium {{ $isEdit ? 'opacity-50 pointer-events-none' : '' }}">
                        @error('no_sn') <span class="text-red-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">Nama Mesin</label>
                        <input wire:model="nama" type="text" placeholder="Contoh: Pintu Depan" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 transition font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">Lokasi</label>
                        <input wire:model="lokasi" type="text" placeholder="Contoh: Gedung A Lt.1" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 transition font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">Secret Token</label>
                        <div class="flex gap-2">
                            <input wire:model="secret_token" type="text" placeholder="Kosongkan bila device tanpa token" class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 transition font-medium">
                            <button type="button" wire:click="generateToken" class="px-4 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition whitespace-nowrap">Generate</button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Device wajib mengirim token ini lewat header <span class="font-mono">X-Device-Token</span>. Biarkan kosong hanya untuk device uji.</p>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 transition">
                            {{ $isEdit ? 'Update' : 'Simpan Device' }}
                        </button>
                        @if($isEdit)
                            <button type="button" wire:click="resetFields" class="px-4 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition">Batal</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- List Device -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Serial Number (SN)</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Identitas</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status Terakhir</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($devices as $dev)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-black font-mono">{{ $dev->no_sn }}</span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-800">{{ $dev->nama ?? 'Unit Belum Dinamai' }}</span>
                                        <span class="text-xs text-slate-500 font-medium">{{ $dev->lokasi ?? 'Lokasi Belum Diatur' }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    @php
                                        $isOnline = $dev->online ? \Carbon\Carbon::parse($dev->online)->diffInMinutes(now()) < 5 : false;
                                    @endphp
                                    <div class="flex items-center">
                                        <span class="w-2 h-2 rounded-full mr-2 {{ $isOnline ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>
                                        <span class="text-xs font-bold {{ $isOnline ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $isOnline ? 'Online' : 'Offline' }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1 font-medium italic">Sinyal: {{ $dev->online ? \Carbon\Carbon::parse($dev->online)->diffForHumans() : 'Belum pernah konek' }}</p>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <button wire:click="edit({{ $dev->id }})" class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button wire:click="delete({{ $dev->id }})" onclick="confirm('Hapus device ini dari monitoring?') || event.stopImmediatePropagation()" class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-8 py-12 text-center text-slate-400 font-medium">Belum ada mesin ZKTeco yang terhubung.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
