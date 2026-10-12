<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AbsensiMonitor;
use App\Livewire\Admin\PresensiGuru;
use App\Models\Attendance;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Keterlambatan absensi per jadwal kelas:
 *  - kelas 1 & 2 : 06:30 - 10:30
 *  - kelas 5 & 6 : 06:30 - 11:30
 *  - kelas 3 & 4 (siang): 12:30 - 16:30
 *
 * Tap masuk setelah jam masuk kelas -> status "Terlambat" (tetap dihitung
 * hadir). Wali kelas mengikuti jam kelas yang diampu; guru/tendik lain tidak
 * pernah dinilai telat.
 */
class KeterlambatanTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@telat.test',
            'username' => 'admin-telat',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);
    }

    private function makeKelas(string $nama, ?Guru $wali = null): Kelas
    {
        return Kelas::create(['nama_kelas' => $nama, 'wali_kelas_id' => $wali?->id]);
    }

    private function makeSiswa(string $nis, Kelas $kelas, string $zktecoId): Siswa
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@telat.test',
            'username' => $nis,
            'password' => bcrypt('secret'),
            'role' => 'siswa',
        ]);

        return Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => 'Siswa '.$nis,
            'kelas_id' => $kelas->id,
            'wajah_id_zkteco' => $zktecoId,
        ]);
    }

    private function makeGuru(string $nip, string $zktecoId): Guru
    {
        $user = User::create([
            'name' => 'Guru '.$nip,
            'email' => $nip.'@telat.test',
            'username' => $nip,
            'password' => bcrypt('secret'),
            'role' => 'guru',
        ]);

        return Guru::create([
            'user_id' => $user->id,
            'nip' => $nip,
            'pin' => $nip,
            'nama' => 'Guru '.$nip,
            'wajah_id_zkteco' => $zktecoId,
        ]);
    }

    private function tap(string $employeeId, string $waktu, string $jenis): void
    {
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

    public function test_siswa_kelas_pagi_telat_bila_tap_lewat_jam_masuk(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('1 A');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->tap('9001', '2026-03-02 06:35:00', 'masuk');

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Terlambat')
            ->assertSee('1 terlambat')
            ->assertDontSee('Alpha');
    }

    public function test_siswa_kelas_pagi_tidak_telat_bila_tap_sebelum_jam_masuk(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('1 A');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->tap('9001', '2026-03-02 06:25:00', 'masuk');

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Hadir')
            ->assertDontSee('Terlambat');
    }

    public function test_siswa_kelas_siang_telat_lewat_1230(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('3 A');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->tap('9001', '2026-03-02 12:35:00', 'masuk');

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Terlambat');
    }

    public function test_siswa_kelas_siang_tidak_telat_sebelum_1230(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('3 A');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->tap('9001', '2026-03-02 12:25:00', 'masuk');

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Hadir')
            ->assertDontSee('Terlambat');
    }

    public function test_wali_kelas_telat_mengikuti_jam_kelas_yang_diampu(): void
    {
        $this->actingAsAdmin();

        $guru = $this->makeGuru('8001', '7001');
        $this->makeKelas('5 A', $guru);
        $this->tap('7001', '2026-03-02 06:40:00', 'masuk');

        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Guru 8001')
            ->assertSee('Terlambat')
            ->assertSee('1 terlambat');
    }

    public function test_guru_bukan_wali_kelas_tidak_dinilai_telat(): void
    {
        $this->actingAsAdmin();

        $this->makeGuru('8001', '7001');
        $this->tap('7001', '2026-03-02 06:40:00', 'masuk');

        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Guru 8001')
            ->assertSee('Hadir')
            ->assertSee('0 terlambat');
    }

    public function test_observer_menandai_tap_kelas_siang_pagi_sebagai_masuk_dan_pulang(): void
    {
        $kelas = $this->makeKelas('3 A');
        $this->makeSiswa('1001', $kelas, '9001');

        // Aturan lama "jam >= 12 = pulang" akan salah menandai tap masuk siang.
        Attendance::create([
            'sn' => 'DEVICE001',
            'table' => 'DEVICE001',
            'stamp' => '2026-03-02 12:35:00',
            'employee_id' => '9001',
            'timestamp' => '2026-03-02 12:35:00',
        ]);

        Attendance::create([
            'sn' => 'DEVICE001',
            'table' => 'DEVICE001',
            'stamp' => '2026-03-02 16:35:00',
            'employee_id' => '9001',
            'timestamp' => '2026-03-02 16:35:00',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => '9001',
            'timestamp' => '2026-03-02 12:35:00',
            'jenis' => 'masuk',
        ]);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => '9001',
            'timestamp' => '2026-03-02 16:35:00',
            'jenis' => 'pulang',
        ]);
    }

    public function test_observer_tetap_memakai_tengah_hari_untuk_guru_tanpa_kelas(): void
    {
        $this->makeGuru('8001', '7001');

        Attendance::create([
            'sn' => 'DEVICE001',
            'table' => 'DEVICE001',
            'stamp' => '2026-03-02 07:00:00',
            'employee_id' => '7001',
            'timestamp' => '2026-03-02 07:00:00',
        ]);

        Attendance::create([
            'sn' => 'DEVICE001',
            'table' => 'DEVICE001',
            'stamp' => '2026-03-02 15:00:00',
            'employee_id' => '7001',
            'timestamp' => '2026-03-02 15:00:00',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => '7001',
            'timestamp' => '2026-03-02 07:00:00',
            'jenis' => 'masuk',
        ]);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => '7001',
            'timestamp' => '2026-03-02 15:00:00',
            'jenis' => 'pulang',
        ]);
    }

    public function test_export_harian_memuat_status_terlambat(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('1 A');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->tap('9001', '2026-03-02 06:35:00', 'masuk');

        $csv = $this->get(route('admin.absensi.download', [
            'periode' => 'harian',
            'tanggal' => '2026-03-02',
        ]))->streamedContent();

        $this->assertStringContainsString('Terlambat', $csv);
    }

    public function test_donut_dashboard_memisahkan_tepat_waktu_dan_terlambat(): void
    {
        $this->actingAsAdmin();

        $hari = now()->toDateString();
        $kelas = $this->makeKelas('1 A');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->makeSiswa('1002', $kelas, '9002');

        // Satu tepat waktu (06:20), satu terlambat (06:40) untuk kelas 1.
        $this->tap('9001', $hari.' 06:20:00', 'masuk');
        $this->tap('9002', $hari.' 06:40:00', 'masuk');

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Terlambat')
            // Persentase "hadir" hanya menghitung yang tepat waktu.
            ->assertSee('Kehadiran Hari Ini: 1 hadir dari 2 siswa');
    }
}
