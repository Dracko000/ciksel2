<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Siswa;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh untuk pengembangan. Aturan yang dipakai di seluruh seed ini:
 * username = NIS/NIP (dipakai juga sebagai employee_id/PIN di mesin ZKTeco)
 * dan password = username.
 *
 * Akun orang tua sengaja tidak di-seed karena kolom users.role tidak
 * memiliki nilai 'ortu'. Modul Ortu di app/Livewire/Ortu tetap dibiarkan
 * apa adanya; seeding parent perlu keputusan terpisah soal role mana yang
 * dipakai.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin
        User::create([
            'name' => 'Administrator Sekolah',
            'email' => 'admin@sdn.com',
            'username' => 'administrator',
            'password' => Hash::make('administrator'),
            'role' => 'admin',
        ]);

        // 2. Create Guru & Kelas
        $nip = '198701012010011001';

        $userGuru = User::create([
            'name' => 'Budi Santoso, S.Pd',
            'email' => 'guru@sdn.com',
            'username' => $nip,
            'password' => Hash::make($nip),
            'role' => 'guru',
        ]);

        $guru = Guru::create([
            'user_id' => $userGuru->id,
            'nip' => $nip,
            'nama' => 'Budi Santoso, S.Pd',
            'pin' => $nip,
            'wajah_id_zkteco' => '101', // Example ZKTeco ID
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => '1A',
            'wali_kelas_id' => $guru->id,
        ]);

        // 3. Create Siswa
        $nis = '24001';

        $userSiswa = User::create([
            'name' => 'Ananda Pratama',
            'email' => 'siswa@sdn.com',
            'username' => $nis,
            'password' => Hash::make($nis),
            'role' => 'siswa',
        ]);

        Siswa::create([
            'user_id' => $userSiswa->id,
            'nis' => $nis,
            'nama' => 'Ananda Pratama',
            'kelas_id' => $kelas->id,
            'pin' => $nis,
            'wajah_id_zkteco' => '2001', // Example ZKTeco ID
        ]);
    }
}