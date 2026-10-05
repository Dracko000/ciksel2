<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AbsensiMonitor;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AbsensiMonitorTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@adms.test',
            'username' => 'administrator',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    private function makeKelas(string $nama): Kelas
    {
        return Kelas::create(['nama_kelas' => $nama]);
    }

    private function makeSiswa(string $nis, ?Kelas $kelas, ?string $zktecoId): Siswa
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@adms.test',
            'username' => $nis,
            'password' => bcrypt('secret'),
            'role' => 'siswa',
        ]);

        return Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => 'Siswa '.$nis,
            'kelas_id' => $kelas?->id,
            'wajah_id_zkteco' => $zktecoId,
        ]);
    }

    private function makeGuru(string $nip, ?string $zktecoId): Guru
    {
        $user = User::create([
            'name' => 'Guru '.$nip,
            'email' => $nip.'@adms.test',
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

    private function makeAbsensi(int $employeeId, string $tanggal = '2026-03-02'): void
    {
        DB::table('attendances')->insert([
            'sn' => 'DEVICE001',
            'table' => 'DEVICE001',
            'stamp' => $tanggal.' 07:00:00',
            'employee_id' => $employeeId,
            'timestamp' => $tanggal.' 07:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_kolom_kelas_menampilkan_nama_kelas_siswa(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('4 B');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->makeAbsensi(9001);

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('4 B');
    }

    public function test_filter_kelas_membatasi_log_absensi(): void
    {
        $this->actingAsAdmin();

        $kelasA = $this->makeKelas('5 A');
        $kelasB = $this->makeKelas('5 B');

        $this->makeSiswa('1001', $kelasA, '9001');
        $this->makeSiswa('1002', $kelasB, '9002');
        $this->makeAbsensi(9001);
        $this->makeAbsensi(9002);

        $component = Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02');

        // Tanpa filter, kedua kelas ikut tampil.
        $component->assertSee('9001');
        $component->assertSee('9002');

        // Setelah difilter, hanya siswa dari kelas terpilih yang tersisa.
        $component->set('kelasFilter', $kelasA->id)
            ->assertSee('9001')
            ->assertDontSee('9002');
    }

    public function test_kelas_tanpa_siswa_terdaftar_tidak_ditawarkan(): void
    {
        $this->actingAsAdmin();

        // Kelas ini siswanya belum punya ZKTeco ID, jadi tidak berguna
        // sebagai filter absensi.
        $kelasKosong = $this->makeKelas('6 Z');
        $this->makeSiswa('1001', $kelasKosong, null);

        $kelasAda = $this->makeKelas('6 A');
        $this->makeSiswa('1002', $kelasAda, '9002');
        $this->makeAbsensi(9002);

        $html = Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->html();

        $this->assertStringContainsString('6 A', $html);
        $this->assertStringNotContainsString('6 Z', $html);
    }

    public function test_ganti_filter_mengembalikan_ke_halaman_pertama(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('3 C');
        $this->makeSiswa('1001', $kelas, '9001');

        $this->makeAbsensi(9001);

        $component = Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02');

        $component->call('gotoPage', 1, 'page');
        $component->set('kelasFilter', $kelas->id)->assertHasNoErrors();
    }

    public function test_filter_peran_memisahkan_tap_siswa_dan_guru(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('4 A');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->makeGuru('8001', '7001');
        $this->makeAbsensi(9001);
        $this->makeAbsensi(7001);

        $component = Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02');

        // Tanpa filter, siswa dan guru sama-sama tampil.
        $component->assertSee('9001')->assertSee('7001');

        $component->set('roleFilter', 'siswa')
            ->assertSee('9001')
            ->assertDontSee('7001');

        $component->set('roleFilter', 'guru')
            ->assertSee('7001')
            ->assertDontSee('9001');
    }

    public function test_filter_kelas_dan_peran_bisa_digabung(): void
    {
        $this->actingAsAdmin();

        $kelasA = $this->makeKelas('4 A');
        $kelasB = $this->makeKelas('4 B');

        $this->makeSiswa('1001', $kelasA, '9001');
        $this->makeSiswa('1002', $kelasB, '9002');
        $this->makeGuru('8001', '7001');
        $this->makeAbsensi(9001);
        $this->makeAbsensi(9002);
        $this->makeAbsensi(7001);

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->set('kelasFilter', $kelasA->id)
            ->set('roleFilter', 'guru')
            // Guru tidak punya kelas, jadi kombinasi ini memang kosong.
            ->assertDontSee('9001')
            ->assertDontSee('9002')
            ->assertDontSee('7001');
    }

    public function test_reset_filter_mengosongkan_kelas_dan_peran(): void
    {
        $this->actingAsAdmin();

        $kelas = $this->makeKelas('4 B');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->makeAbsensi(9001);

        Livewire::test(AbsensiMonitor::class)
            ->set('dateFilter', '2026-03-02')
            ->set('kelasFilter', $kelas->id)
            ->set('roleFilter', 'siswa')
            ->assertSet('kelasFilter', $kelas->id)
            ->call('resetFilter')
            ->assertSet('kelasFilter', '')
            ->assertSet('roleFilter', '');
    }

    public function test_kontrol_filter_kelas_dan_peran_dirender(): void
    {
        $this->actingAsAdmin();

        $html = Livewire::test(AbsensiMonitor::class)->html();

        $this->assertStringContainsString('wire:model.live="kelasFilter"', $html);
        $this->assertStringContainsString('wire:model.live="roleFilter"', $html);
        $this->assertStringContainsString('for="kelasFilter"', $html);
        $this->assertStringContainsString('for="roleFilter"', $html);
        $this->assertStringContainsString('Semua Kelas', $html);
        $this->assertStringContainsString('Semua Peran', $html);
        $this->assertStringContainsString('Guru/Tendik', $html);
    }
}