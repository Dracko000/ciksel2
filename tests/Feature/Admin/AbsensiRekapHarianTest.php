<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AbsensiMonitor;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Monitor Absensi kini menampilkan rekap harian per orang (bukan log tap
 * mentah), dikelompokkan per kelas.
 */
class AbsensiRekapHarianTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@rekap.test',
            'username' => 'admin-rekap',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);
    }

    private function buatSiswa(string $nis, Kelas $kelas, string $zktecoId): Siswa
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@rekap.test',
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

    private function tap(int $employeeId, string $waktu): void
    {
        DB::table('attendances')->insert([
            'sn' => 'DEVICE001',
            'table' => 'DEVICE001',
            'stamp' => $waktu,
            'employee_id' => $employeeId,
            'timestamp' => $waktu,
            'jenis' => (int) date('H', strtotime($waktu)) >= 12 ? 'pulang' : 'masuk',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_siswa_dengan_tap_menampilkan_jam_masuk_dan_status_hadir(): void
    {
        $this->actingAsAdmin();

        $kelas = Kelas::create(['nama_kelas' => '4 B']);
        $this->buatSiswa('1001', $kelas, '9001');
        $this->tap(9001, '2026-03-02 07:05:00');
        $this->tap(9001, '2026-03-02 15:00:00');

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('4 B')
            ->assertSee('07:05')
            ->assertSee('15:00')
            ->assertSee('Hadir');
    }

    public function test_siswa_tanpa_tap_tercatat_alpha(): void
    {
        $this->actingAsAdmin();

        $kelas = Kelas::create(['nama_kelas' => '5 A']);
        $this->buatSiswa('1001', $kelas, '9001');

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('5 A')
            ->assertSee('Alpha')
            ->assertDontSee('Hadir');
    }

    public function test_tap_pulang_saja_alpha_tapi_jam_pulang_tetap_tampil(): void
    {
        $this->actingAsAdmin();

        $kelas = Kelas::create(['nama_kelas' => '6 C']);
        $this->buatSiswa('1001', $kelas, '9001');

        // Hanya ada log pulang: kehadiran tidak dihitung, tapi jamnya tampil.
        $this->tap(9001, '2026-03-02 15:00:00');

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('15:00')
            ->assertSee('Alpha')
            ->assertDontSee('Hadir');
    }

    public function test_kolom_kehadiran_hanya_menghitung_log_masuk(): void
    {
        $this->actingAsAdmin();

        // Kelas 6 masuk 06:30: tap 06:20 masih tepat waktu sehingga baris ini
        // menguji "log masuk = hadir", bukan keterlambatan.
        $kelas = Kelas::create(['nama_kelas' => '6 D']);
        $this->buatSiswa('1001', $kelas, '9001');
        $this->tap(9001, '2026-03-02 06:20:00');
        $this->tap(9001, '2026-03-02 15:00:00');

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Jumlah Masuk')
            ->assertSee('Hadir');
    }

    public function test_rekap_dipaginasi_20_baris_per_halaman(): void
    {
        $this->actingAsAdmin();

        $kelas = Kelas::create(['nama_kelas' => '1 A']);

        for ($i = 1; $i <= 25; $i++) {
            $this->buatSiswa(sprintf('20%02d', $i), $kelas, (string) (9000 + $i));
        }

        $component = Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02');

        // Halaman 1 berisi 20 baris pertama; siswa ke-21 dst. menunggu.
        $component->assertSee('2020')->assertDontSee('2025');
        $component->assertSee('data absensi harian');

        $component->call('gotoPage', 2, 'page')
            ->assertSee('2025')
            ->assertDontSee('2020');
    }

    public function test_tombol_unduh_periode_tampil_di_halaman(): void
    {
        $this->actingAsAdmin();

        $html = Livewire::test(AbsensiMonitor::class)->html();

        $this->assertStringContainsString('periode=harian', $html);
        $this->assertStringContainsString('periode=mingguan', $html);
        $this->assertStringContainsString('periode=bulanan', $html);
        $this->assertStringContainsString('/admin/absensi/download', $html);
    }
}
