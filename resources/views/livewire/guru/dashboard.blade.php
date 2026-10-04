<div>
    <h2 class="text-2xl font-bold mb-4">Dashboard Guru</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <a href="{{ route('guru.absensi') }}" class="bg-blue-600 rounded-lg shadow-md p-6 text-white hover:bg-blue-700 transition">
            <h3 class="text-xl font-bold mb-2">Absensi Kelas</h3>
            <p class="text-blue-100">Pantau kehadiran siswa di kelas Anda hari ini.</p>
        </a>
        <a href="#" class="bg-green-600 rounded-lg shadow-md p-6 text-white hover:bg-green-700 transition">
            <h3 class="text-xl font-bold mb-2">Absen Manual</h3>
            <p class="text-green-100">Input absensi manual jika siswa tidak membawa kartu.</p>
        </a>
        <a href="#" class="bg-purple-600 rounded-lg shadow-md p-6 text-white hover:bg-purple-700 transition">
            <h3 class="text-xl font-bold mb-2">Jadwal Mengajar</h3>
            <p class="text-purple-100">Kelola dan lihat jadwal mengajar Anda.</p>
        </a>
    </div>

    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <h3 class="text-lg font-bold mb-4 border-b pb-2">Jadwal Mengajar Hari Ini</h3>
        @if($jadwalHariIni->isEmpty())
            <p class="text-gray-500">Tidak ada jadwal mengajar hari ini.</p>
        @else
            <ul class="divide-y divide-gray-200">
                @foreach($jadwalHariIni as $jadwal)
                <li class="py-4 flex">
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">{{ $jadwal->mata_pelajaran }} - Kelas {{ $jadwal->kelas->nama_kelas }}</p>
                        <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }} s/d {{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}</p>
                    </div>
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
