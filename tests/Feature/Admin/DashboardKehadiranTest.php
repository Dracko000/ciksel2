<?php

namespace Tests\Feature\Admin;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dashboard admin menampilkan kehadiran hari ini sebagai donut (hadir vs
 * tidak hadir) yang menggantikan dua kartu angka.
 */
class DashboardKehadiranTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@dashboard.test',
            'username' => 'admin-dashboard',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);
    }

    private function makeSiswa(string $nis, string $zktecoId, int $kelasId): Siswa
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@dashboard.test',
            'username' => $nis,
            'password' => bcrypt('secret'),
            'role' => 'siswa',
        ]);

        return Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => 'Siswa '.$nis,
            'kelas_id' => $kelasId,
            'wajah_id_zkteco' => $zktecoId,
        ]);
    }

    private function tap(string $employeeId, string $jenis): void
    {
        // Waktu pagi dipatok supaya status tidak berubah tergantung jam test
        // dijalankan (tap sore bisa terbaca sebagai terlambat).
        $waktu = now()->setTime(6, 20);

        DB::table('attendances')->insert([
            'sn' => 'DEVICE001',
            'table' => 'DEVICE001',
            'stamp' => $waktu,
            'employee_id' => $employeeId,
            'timestamp' => $waktu,
            'jenis' => $jenis,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_donut_kehadiran_menghitung_siswa_hadir_dari_total(): void
    {
        $this->actingAsAdmin();

        $kelas = Kelas::create(['nama_kelas' => '4 B']);
        $this->makeSiswa('1001', '9001', $kelas->id);
        $this->makeSiswa('1002', '9002', $kelas->id);

        $this->tap('9001', 'masuk');

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Kehadiran Hari Ini')
            ->assertSee('Kehadiran Hari Ini: 1 hadir dari 2 siswa')
            ->assertSee('50%')
            ->assertSee('Tidak Hadir');
    }

    public function test_tap_pulang_tidak_menambah_kehadiran_di_donut(): void
    {
        $this->actingAsAdmin();

        $kelas = Kelas::create(['nama_kelas' => '4 B']);
        $this->makeSiswa('1001', '9001', $kelas->id);

        // Hanya tap pulang: siswa tidak boleh terhitung hadir.
        $this->tap('9001', 'pulang');

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Kehadiran Hari Ini: 0 hadir dari 1 siswa')
            ->assertSee('0%');
    }
}
