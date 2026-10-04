<div>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Input Nilai E-Raport</h2>
            <p class="text-slate-500 font-medium">Manajemen nilai siswa untuk semester ini.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-2xl relative mb-6 font-bold">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Form Side -->
        <div class="lg:col-span-1">
            <x-card title="Entry Nilai Baru">
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Pilih Kelas</label>
                        <select wire:model.live="kelas_id" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 outline-none transition">
                            @foreach($kelasList as $kelas)
                                <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Pilih Siswa</label>
                        <select wire:model.live="siswa_id" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 outline-none transition">
                            @foreach($siswaList as $siswa)
                                <option value="{{ $siswa->id }}">{{ $siswa->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Mata Pelajaran</label>
                        <input wire:model="mata_pelajaran" type="text" placeholder="Contoh: Matematika" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 outline-none transition">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Nilai (0-100)</label>
                            <input wire:model="nilai_angka" type="number" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Semester</label>
                            <select wire:model.live="semester" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 outline-none transition">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 transition transform hover:-translate-y-0.5">
                        Simpan Nilai
                    </button>
                </form>
            </x-card>
        </div>

        <!-- Table Side -->
        <div class="lg:col-span-2">
            <x-card title="Daftar Nilai Siswa Terpilih">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Mata Pelajaran</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Nilai</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Predikat</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($existingNilai as $n)
                            <tr>
                                <td class="px-6 py-4 text-sm font-bold text-slate-700">{{ $n->mata_pelajaran }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-lg font-black text-sm">{{ $n->nilai_angka }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $predikat = 'E';
                                        $color = 'text-red-500';
                                        if($n->nilai_angka >= 85) { $predikat = 'A'; $color = 'text-green-600'; }
                                        elseif($n->nilai_angka >= 75) { $predikat = 'B'; $color = 'text-blue-600'; }
                                        elseif($n->nilai_angka >= 60) { $predikat = 'C'; $color = 'text-yellow-600'; }
                                    @endphp
                                    <span class="font-black {{ $color }}">{{ $predikat }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="text-slate-400 hover:text-red-500 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</div>
