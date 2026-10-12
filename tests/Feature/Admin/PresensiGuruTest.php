<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\PresensiGuru;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman Presensi Guru: rekap kehadiran guru & tendik dari tap ZKTeco.
 *
 * Hanya guru/tendik yang punya ID mesin (wajah_id_zkteco) yang tampil, dan
 * siswa tidak pernah ikut walau punya tap pada hari yang sama.
 */
class PresensiGuruTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@presensi-guru.test',
            'username' => 'admin-presensi-guru',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);
    }

    private function makeSiswa(string $nis, string $zktecoId): Siswa
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@presensi-guru.test',
            'username' => $nis,
            'password' => bcrypt('secret'),
            'role' => 'siswa',
        ]);

        return Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => 'Siswa '.$nis,
            'kelas_id' => Kelas::create(['nama_kelas' => '4 B'])->id,
            'wajah_id_zkteco' => $zktecoId,
        ]);
    }

    private function makeGuru(string $nama, string $nip, ?string $zktecoId): Guru
    {
        $user = User::create([
            'name' => $nama,
            'email' => $nip.'@presensi-guru.test',
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

    public function test_guru_tap_pulang_saja_alpha_tapi_jam_pulang_tampil(): void
    {
        $this->actingAsAdmin();

        $this->makeGuru('Guru Pulang', '8001', '7001');
        $this->tap(7001, '2026-03-02 15:00:00');

        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('15:00')
            ->assertSee('Alpha')
            ->assertSee('1 alpha')
            ->assertDontSee('1 hadir');
    }

    public function test_hanya_guru_dan_tendik_yang_tampil(): void
    {
        $this->actingAsAdmin();

        $this->makeSiswa('1001', '9001');
        $this->makeGuru('Guru Hadir', '8001', '7001');
        $this->tap(9001);
        $this->tap(7001);

        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Guru Hadir')
            ->assertDontSee('Siswa 1001');
    }

    public function test_guru_tanpa_id_mesin_tidak_ditampilkan(): void
    {
        $this->actingAsAdmin();

        $this->makeGuru('Guru Tanpa Id', '8001', null);
        $this->makeGuru('Guru Ber Id', '8002', '7002');
        $this->tap(7002);

        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Guru Ber Id')
            ->assertDontSee('Guru Tanpa Id');
    }

    public function test_status_hadir_dan_alpha_dihitung(): void
    {
        $this->actingAsAdmin();

        $this->makeGuru('Guru Masuk', '8001', '7001');
        $this->makeGuru('Guru Bolos', '8002', '7002');
        $this->tap(7001, '2026-03-02 06:55:00');

        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Guru Masuk')
            ->assertSee('Guru Bolos')
            ->assertSee('Hadir')
            ->assertSee('Alpha')
            ->assertSee('06:55')
            ->assertSee('1 hadir')
            ->assertSee('1 alpha');
    }

    public function test_pencarian_menyaring_berdasarkan_nama_atau_nip(): void
    {
        $this->actingAsAdmin();

        $this->makeGuru('Ani Sutarni', '8001', '7001');
        $this->makeGuru('Budi Santoso', '8002', '7002');

        $component = Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02');

        $component->set('search', 'Ani')
            ->assertSee('Ani Sutarni')
            ->assertDontSee('Budi Santoso');

        $component->set('search', '8002')
            ->assertSee('Budi Santoso')
            ->assertDontSee('Ani Sutarni');
    }

    public function test_donut_kehadiran_guru_menampilkan_hadir_dan_tidak_hadir(): void
    {
        $this->actingAsAdmin();

        $this->makeGuru('Guru Masuk', '8001', '7001');
        $this->makeGuru('Guru Bolos', '8002', '7002');
        $this->tap(7001, '2026-03-02 06:55:00');

        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Kehadiran Guru')
            ->assertSee('Kehadiran Guru: 1 hadir dari 2 guru')
            ->assertSee('Total Guru')
            ->assertSee('50%');
    }

    public function test_donut_kehadiran_guru_tidak_berubah_saat_pencarian(): void
    {
        $this->actingAsAdmin();

        $this->makeGuru('Guru Masuk', '8001', '7001');
        $this->makeGuru('Guru Bolos A', '8002', '7002');
        $this->makeGuru('Guru Bolos B', '8003', '7003');
        $this->tap(7001, '2026-03-02 06:55:00');

        // Donut tetap menunjukkan ringkasan sehari (1 dari 3), walau tabel
        // disaring jadi satu guru saja.
        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->set('search', 'Guru Masuk')
            ->assertSee('Kehadiran Guru: 1 hadir dari 3 guru')
            ->assertDontSee('Guru Bolos A');
    }

    public function test_tombol_unduh_mengarah_ke_export_guru(): void
    {
        $this->actingAsAdmin();

        $html = Livewire::test(PresensiGuru::class)->html();

        $this->assertStringContainsString('peran=guru', $html);
        $this->assertStringContainsString('periode=harian', $html);
        $this->assertStringContainsString('periode=mingguan', $html);
        $this->assertStringContainsString('periode=bulanan', $html);
    }

    public function test_halaman_dirender_lewat_route_admin(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.presensi-guru'))->assertOk();
    }
}
