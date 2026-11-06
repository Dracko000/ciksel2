<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AturJamGuru;
use App\Livewire\Admin\AturJamKelas;
use App\Livewire\Admin\PresensiGuru;
use App\Livewire\Admin\PresensiMurid;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Absensi\JamGuru;
use App\Support\Absensi\JamKelas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menu "Atur Jam": mengatur jam kelas dan jam guru, dan memastikan nilai yang
 * disimpan benar-benar dipakai untuk menilai keterlambatan.
 */
class AturJamTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@atur-jam.test',
            'username' => 'admin-atur-jam',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);
    }

    private function makeSiswa(string $nis, string $zktecoId, int $kelasId): void
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@atur-jam.test',
            'username' => $nis,
            'password' => bcrypt($nis),
            'role' => 'siswa',
        ]);

        Siswa::create([
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
            'email' => $nip.'@atur-jam.test',
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

    public function test_jam_kelas_bawaan_dipakai_sebelum_diatur(): void
    {
        $this->assertSame('06:30', JamKelas::jamMasuk('1 A'));
        $this->assertTrue(JamKelas::terlambat('2026-03-02 07:30:00', '1 A'));
    }

    public function test_simpan_jam_kelas_tersimpan_dan_mengubah_keterlambatan(): void
    {
        $this->actingAsAdmin();

        Livewire::test(AturJamKelas::class)
            ->set('jadwal.1.masuk', '08:00')
            ->set('jadwal.1.pulang', '10:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pengaturan_jam', [
            'kategori' => 'kelas',
            'tingkat' => 1,
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '10:00:00',
        ]);

        $this->assertSame('08:00', JamKelas::jamMasuk('1 A'));
        $this->assertFalse(JamKelas::terlambat('2026-03-02 07:30:00', '1 A'));
        $this->assertTrue(JamKelas::terlambat('2026-03-02 08:30:00', '1 A'));
    }

    public function test_jam_kelas_baru_dipakai_halaman_presensi_murid(): void
    {
        $this->actingAsAdmin();

        $kelas = Kelas::create(['nama_kelas' => '1 A']);
        $this->makeSiswa('1001', '9001', $kelas->id);
        $this->tap(9001, '2026-03-02 07:30:00');

        // Bawaan tingkat 1 = 06:30, jadi tap 07:30 terhitung terlambat.
        Livewire::test(PresensiMurid::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('1 terlambat');

        Livewire::test(AturJamKelas::class)
            ->set('jadwal.1.masuk', '08:00')
            ->set('jadwal.1.pulang', '10:00')
            ->call('save');

        // Setelah jam masuk jadi 08:00, tap yang sama tidak lagi terlambat.
        Livewire::test(PresensiMurid::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('0 terlambat');
    }

    public function test_simpan_jam_guru_tersimpan_per_orang(): void
    {
        $this->actingAsAdmin();

        $guru = $this->makeGuru('Guru Satu', '8001', '7001');

        Livewire::test(AturJamGuru::class)
            ->set("jadwal.{$guru->id}.masuk", '07:00')
            ->set("jadwal.{$guru->id}.pulang", '14:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('guru', [
            'id' => $guru->id,
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '14:00:00',
        ]);

        // Jam global lama tidak dipakai lagi.
        $this->assertDatabaseMissing('pengaturan_jam', ['kategori' => 'guru']);

        $this->assertSame('07:00', JamGuru::jam($guru->fresh()->jam_masuk));
        $this->assertTrue(JamGuru::terlambat('2026-03-02 07:30:00', $guru->fresh()->jam_masuk));
    }

    public function test_jam_guru_baru_dipakai_halaman_presensi_guru(): void
    {
        $this->actingAsAdmin();

        // Guru tanpa wali kelas: bawaannya tidak pernah dinilai telat.
        $guru = $this->makeGuru('Guru Telat', '8001', '7001');
        $this->tap(7001, '2026-03-02 07:30:00');

        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('0 terlambat');

        Livewire::test(AturJamGuru::class)
            ->set("jadwal.{$guru->id}.masuk", '07:00')
            ->set("jadwal.{$guru->id}.pulang", '14:00')
            ->call('save');

        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('1 terlambat');
    }

    public function test_jam_pribadi_guru_mengalahkan_jam_kelas_wali(): void
    {
        $this->actingAsAdmin();

        // Wali kelas 5 A (jam kelas 06:30), tetapi jam pribadinya 08:00.
        $guru = $this->makeGuru('Guru Wali', '8001', '7001');
        $kelas = Kelas::create(['nama_kelas' => '5 A', 'wali_kelas_id' => $guru->id]);
        $this->tap(7001, '2026-03-02 07:30:00');

        // Sebelum diatur, guru mengikuti jam kelas 5 A -> tap 07:30 terlambat.
        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('1 terlambat');

        Livewire::test(AturJamGuru::class)
            ->set("jadwal.{$guru->id}.masuk", '08:00')
            ->set("jadwal.{$guru->id}.pulang", '14:00')
            ->call('save');

        // Jam pribadi (08:00) menang atas jam kelas (06:30).
        Livewire::test(PresensiGuru::class)
            ->set('dateFilter', '2026-03-02')
            ->assertSee('Guru Wali')
            ->assertSee('0 terlambat');
    }

    public function test_halaman_atur_jam_dirender(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.jam-guru'))->assertOk()->assertSee('Jam Guru');
        $this->get(route('admin.jam-kelas'))->assertOk()->assertSee('Jam Kelas');
    }
}
