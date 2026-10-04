<div>
    <h2 class="text-2xl font-bold mb-4">Manajemen Pengguna</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <form wire:submit.prevent="store">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama</label>
                    <input wire:model="name" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input wire:model="email" type="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input wire:model="password" type="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Peran (Role)</label>
                    <select wire:model.live="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="siswa">Siswa</option>
                        <option value="guru">Guru / Tendik</option>
                        <option value="ortu">Orang Tua</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                @if($role === 'siswa')
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">NIS</label>
                        <input wire:model="nis" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kelas</label>
                        <select wire:model="kelas_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Pilih Kelas</option>
                            @foreach($kelasList as $kelas)
                                <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($role === 'guru')
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">NIP / NUPTK</label>
                        <input wire:model="nip" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4 flex items-center">
                        <input wire:model="is_tendik" type="checkbox" class="mr-2 leading-tight">
                        <span class="text-sm">Apakah Staff Tendik?</span>
                    </div>
                @endif

                @if($role === 'siswa' || $role === 'guru')
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ZKTeco Employee ID (Wajah/Jari)</label>
                        <input wire:model="wajah_id_zkteco" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <p class="text-xs text-gray-500 mt-1">ID ini akan disinkronkan dengan log dari mesin absensi ZKTeco.</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">RFID Kartu</label>
                        <input wire:model="rfid_kartu" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end mt-4">
                <button type="button" wire:click="resetFields" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">Batal</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded">
                    {{ $isEdit ? 'Update' : 'Simpan' }}
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-md rounded my-6">
        <table class="text-left w-full border-collapse">
            <thead>
                <tr>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Nama</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Email</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Role</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">ZKTeco ID</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="hover:bg-grey-lighter">
                    <td class="py-4 px-6 border-b border-grey-light">{{ $user->name }}</td>
                    <td class="py-4 px-6 border-b border-grey-light">{{ $user->email }}</td>
                    <td class="py-4 px-6 border-b border-grey-light"><span class="bg-blue-100 text-blue-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded">{{ ucfirst($user->role) }}</span></td>
                    <td class="py-4 px-6 border-b border-grey-light">
                        @if($user->role === 'siswa' && $user->siswa)
                            {{ $user->siswa->wajah_id_zkteco ?? '-' }}
                        @elseif($user->role === 'guru' && $user->guru)
                            {{ $user->guru->wajah_id_zkteco ?? '-' }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="py-4 px-6 border-b border-grey-light">
                        <button wire:click="edit({{ $user->id }})" class="text-white font-bold py-1 px-3 rounded text-xs bg-green-500 hover:bg-green-600">Edit</button>
                        <button wire:click="delete({{ $user->id }})" class="text-white font-bold py-1 px-3 rounded text-xs bg-red-500 hover:bg-red-600">Hapus</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
