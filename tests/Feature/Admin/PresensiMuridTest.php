<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\PresensiMurid;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman Presensi Murid: rekap kehadiran siswa dari tap ZKTeco.
 *
 * Hanya siswa yang punya ID mesin (wajah_id_zkteco) yang tampil, dan guru
 * tidak pernah ikut walau punya tap pada hari yang sama.
 */
class PresensiMuridTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@presensi-murid.test',
            'username' => 'admin-presensi-murid',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);
    }

    private function makeKelas(string $nama): Kelas
    {
        return Kelas::create(['nama_kelas' => $nama]);
    }

    private function makeSiswa(string $nis, string $zktecoId, int $kelasId): Siswa
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@presensi-murid.test',
            'username' => $nis,
            'password' => bcrypt($nis),
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

    private function makeGuru(string $nama, string $nip, ?string $zktecoId): Guru
    {
        $user = User::create([
            'name' => $nama,
            'email' => $nip.'@presensi-murid.test',
            'username' => $nip,
            'password' => bcrypt('secret'),
            'role' => 'guru',
        ]);

        return Guru::create([
            'user_id' => $user->id,
            'nip' => $nip,
            'pin' => $nip,
            'nama' => $nama,
            'wajah_id_zkteco' => $zktecoId,
        ]);
    }

    private function tap(int $employeeId, string $waktu = '2026-03-02 07:00:00'): void
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

    public function test_hanya_siswa_yang_tampil(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('4 B');
        $this->makeSiswa('1001', '9001', $kelas->id);
        $this->makeGuru('Guru Hadir', '8001', '7001');
        $this->tap(9001);
        $this->tap(7001);

        Livewire::test(PresensiMurid::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Siswa 1001')
            ->assertDontSee('Guru Hadir');
    }

    public function test_status_hadir_dan_alpha_dihitung(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('4 B');
        $this->makeSiswa('1001', '9001', $kelas->id);
        $this->makeSiswa('1002', '9002', $kelas->id);
        $this->tap(9001, '2026-03-02 06:55:00');

        Livewire::test(PresensiMurid::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Siswa 1001')
            ->assertSee('Siswa 1002')
            ->assertSee('Hadir')
            ->assertSee('Alpha')
            ->assertSee('06:55')
            ->assertSee('1 hadir')
            ->assertSee('1 alpha');
    }

    public function test_filter_kelas_menyaring_siswa(): void
    {
        $this->actingAsAdmin();

        $kelasA = $this->makeKelas('1 A');
        $kelasB = $this->makeKelas('1 B');
        $this->makeSiswa('1001', '9001', $kelasA->id);
        $this->makeSiswa('1002', '9002', $kelasB->id);

        Livewire::test(PresensiMurid::class)
            ->set('dateFilter', '2026-03-02')
            ->set('kelasFilter', (string) $kelasA->id)
            ->assertSee('Siswa 1001')
            ->assertDontSee('Siswa 1002');
    }

    public function test_pencarian_menyaring_nama_atau_nis(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('4 B');
        $this->makeSiswa('1001', '9001', $kelas->id);
        $this->makeSiswa('1002', '9002', $kelas->id);

        $component = Livewire::test(PresensiMurid::class)
            ->set('dateFilter', '2026-03-02');

        $component->set('search', '1001')
            ->assertSee('Siswa 1001')
            ->assertDontSee('Siswa 1002');

        $component->set('search', '1002')
            ->assertSee('Siswa 1002')
            ->assertDontSee('Siswa 1001');
    }

    public function test_tap_belum_terdaftar_tampil_dengan_rfid_dari_mesin(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('4 B');
        $this->makeSiswa('1001', '9001', $kelas->id);
        $this->tap(9001, '2026-03-02 06:55:00');
        $this->tap(8888, '2026-03-02 07:20:00');

        Livewire::test(PresensiMurid::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('RFID Belum Terdaftar')
            ->assertSee('8888')
            ->assertSee('07:20')
            ->assertSee('1 RFID');
    }

    public function test_tap_belum_terdaftar_tetap_tampil_saat_filter_kelas_aktif(): void
    {
        $this->actingAsAdmin();

        $kelasA = $this->makeKelas('1 A');
        $this->makeSiswa('1001', '9001', $kelasA->id);
        $this->tap(7777, '2026-03-02 07:30:00');

        Livewire::test(PresensiMurid::class)
            ->set('dateFilter', '2026-03-02')
            ->set('kelasFilter', (string) $kelasA->id)
            ->assertSee('7777')
            ->assertSee('1 RFID');
    }

    public function test_tombol_unduh_mengarah_ke_export_siswa(): void
    {
        $this->actingAsAdmin();

        $html = Livewire::test(PresensiMurid::class)->html();

        $this->assertStringContainsString('peran=siswa', $html);
        $this->assertStringContainsString('periode=harian', $html);
        $this->assertStringContainsString('periode=mingguan', $html);
        $this->assertStringContainsString('periode=bulanan', $html);
    }

    public function test_halaman_dirender_lewat_route_admin(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.presensi-murid'))->assertOk();
    }
}
