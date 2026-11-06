<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RfidBelumTerdaftar;
use App\Models\Device;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman "RFID Belum Terdaftar": mengumpulkan tap mesin yang belum punya
 * pemilik lalu menghubungkannya ke satu siswa/guru.
 *
 * Definisi "belum terdaftar" harus sama dengan halaman Presensi, yaitu RFID
 * yang tidak cocok dengan wajah_id_zkteco siapa pun.
 */
class RfidBelumTerdaftarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-27 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function actingAsAdmin(): User
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@rfid.test',
            'username' => 'admin-rfid',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    private function makeSiswa(string $nis, ?string $zktecoId = null): Siswa
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@rfid.test',
            'username' => $nis,
            'password' => bcrypt('secret'),
            'role' => 'siswa',
        ]);

        return Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => 'Siswa '.$nis,
            'kelas_id' => Kelas::create(['nama_kelas' => 'Kelas '.$nis])->id,
            'wajah_id_zkteco' => $zktecoId,
        ]);
    }

    private function makeGuru(string $nama, string $nip, ?string $zktecoId = null): Guru
    {
        $user = User::create([
            'name' => $nama,
            'email' => $nip.'@rfid.test',
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

    private function tap(string $rfid, string $waktu, string $jenis = 'masuk', string $sn = 'DEVICE001'): void
    {
        DB::table('attendances')->insert([
            'sn' => $sn,
            'table' => $sn,
            'stamp' => $waktu,
            'employee_id' => $rfid,
            'timestamp' => $waktu,
            'jenis' => $jenis,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_menampilkan_rfid_asing_dan_menyembunyikan_yang_sudah_terdaftar(): void
    {
        $this->actingAsAdmin();

        Device::create(['nama' => 'Mesin Absensi 1', 'no_sn' => 'DEVICE001']);

        $this->makeSiswa('1001', '7001');
        $this->tap('7001', '2026-10-27 06:05:00');
        $this->tap('9001', '2026-10-26 06:10:00');
        $this->tap('9001', '2026-10-26 13:40:00', 'pulang');
        $this->tap('9001', '2026-10-27 06:15:00');

        $component = Livewire::test(RfidBelumTerdaftar::class)
            ->assertSee('9001')
            ->assertSee('Mesin Absensi 1')
            ->assertDontSee('7001');

        $taps = $component->viewData('taps');

        $this->assertCount(1, $taps);
        $this->assertSame('9001', $taps[0]['employee_id']);
        $this->assertSame(3, $taps[0]['jumlahTap']);
        $this->assertSame('06:10', $taps[0]['jamMasuk']);
        $this->assertSame('13:40', $taps[0]['jamPulang']);
        $this->assertSame(
            [['sn' => 'DEVICE001', 'nama' => 'Mesin Absensi 1', 'jumlah' => 3]],
            $taps[0]['mesin']
        );
        $this->assertSame(3, $component->viewData('totalTap'));
    }

    public function test_menampilkan_semua_mesin_sumber_bila_tap_berasal_dari_lebih_dari_satu(): void
    {
        $this->actingAsAdmin();

        Device::create(['nama' => 'Mesin Absensi 1', 'no_sn' => 'DEVICE001']);
        Device::create(['nama' => 'Mesin Absensi 2', 'no_sn' => 'DEVICE002']);

        $this->tap('9001', '2026-10-26 06:10:00');
        $this->tap('9001', '2026-10-27 06:15:00');
        $this->tap('9001', '2026-10-27 13:40:00', 'pulang', 'DEVICE002');

        $taps = Livewire::test(RfidBelumTerdaftar::class)
            ->assertSee('Mesin Absensi 1')
            ->assertSee('Mesin Absensi 2')
            ->viewData('taps');

        $this->assertSame(
            [
                ['sn' => 'DEVICE001', 'nama' => 'Mesin Absensi 1', 'jumlah' => 2],
                ['sn' => 'DEVICE002', 'nama' => 'Mesin Absensi 2', 'jumlah' => 1],
            ],
            $taps[0]['mesin']
        );
    }

    public function test_pencarian_menyaring_rfid(): void
    {
        $this->actingAsAdmin();

        $this->tap('9001', '2026-10-27 06:05:00');
        $this->tap('9002', '2026-10-27 06:06:00');

        $taps = Livewire::test(RfidBelumTerdaftar::class)
            ->set('search', '9002')
            ->viewData('taps');

        $this->assertCount(1, $taps);
        $this->assertSame('9002', $taps[0]['employee_id']);
    }

    public function test_filter_mesin_menyaring_rfid_berdasarkan_sumber_tap(): void
    {
        $this->actingAsAdmin();

        Device::create(['nama' => 'Mesin Absensi 1', 'no_sn' => 'DEVICE001']);
        Device::create(['nama' => 'Mesin Absensi 2', 'no_sn' => 'DEVICE002']);

        $this->tap('9001', '2026-10-27 06:00:00', 'masuk', 'DEVICE001');
        $this->tap('9002', '2026-10-27 06:01:00', 'masuk', 'DEVICE002');
        $this->tap('9003', '2026-10-27 06:02:00', 'masuk', 'DEVICE002');

        $taps = Livewire::test(RfidBelumTerdaftar::class)
            ->set('mesinFilter', 'DEVICE002')
            ->viewData('taps');

        $this->assertEqualsCanonicalizing(['9002', '9003'], array_column($taps, 'employee_id'));
    }

    public function test_rentang_hari_menyaring_tap_lama(): void
    {
        $this->actingAsAdmin();

        $this->tap('9001', '2026-09-01 06:00:00');
        $this->tap('9002', '2026-10-27 06:00:00');

        $semua = Livewire::test(RfidBelumTerdaftar::class)->viewData('taps');
        $this->assertCount(2, $semua);

        $tigaPuluhHari = Livewire::test(RfidBelumTerdaftar::class)
            ->set('rentang', '30')
            ->viewData('taps');

        $this->assertCount(1, $tigaPuluhHari);
        $this->assertSame('9002', $tigaPuluhHari[0]['employee_id']);
    }

    public function test_hubungkan_rfid_ke_siswa(): void
    {
        $this->actingAsAdmin();

        $siswa = $this->makeSiswa('1001');
        $this->tap('9001', '2026-10-27 06:15:00');

        Livewire::test(RfidBelumTerdaftar::class)
            ->call('bukaAssign', '9001')
            ->set('selectedUserId', $siswa->user_id)
            ->call('hubungkan')
            ->assertHasNoErrors()
            ->assertSet('assigningRfid', '')
            ->assertSet('selectedUserId', null);

        $siswa->refresh();

        $this->assertSame('9001', $siswa->wajah_id_zkteco);
        $this->assertSame('9001', $siswa->rfid_kartu);

        // Setelah dihubungkan, RFID tidak lagi muncul sebagai tap asing.
        $this->assertSame([], Livewire::test(RfidBelumTerdaftar::class)->viewData('taps'));
    }

    public function test_hubungkan_rfid_ke_guru(): void
    {
        $this->actingAsAdmin();

        $guru = $this->makeGuru('Guru Baru', '8001');
        $this->tap('9001', '2026-10-27 06:20:00');

        Livewire::test(RfidBelumTerdaftar::class)
            ->call('bukaAssign', '9001')
            ->set('selectedUserId', $guru->user_id)
            ->call('hubungkan')
            ->assertHasNoErrors();

        $guru->refresh();

        $this->assertSame('9001', $guru->wajah_id_zkteco);
        $this->assertSame('9001', $guru->rfid_kartu);
    }

    public function test_menolak_pengguna_yang_sudah_punya_id_mesin(): void
    {
        $this->actingAsAdmin();

        $siswa = $this->makeSiswa('1001', '7001');
        $this->tap('9001', '2026-10-27 06:15:00');

        Livewire::test(RfidBelumTerdaftar::class)
            ->call('bukaAssign', '9001')
            ->set('selectedUserId', $siswa->user_id)
            ->call('hubungkan')
            ->assertHasErrors(['selectedUserId']);

        $this->assertSame('7001', $siswa->fresh()->wajah_id_zkteco);
    }

    public function test_menolak_user_tanpa_data_siswa_atau_guru(): void
    {
        $admin = $this->actingAsAdmin();

        $this->tap('9001', '2026-10-27 06:15:00');

        Livewire::test(RfidBelumTerdaftar::class)
            ->call('bukaAssign', '9001')
            ->set('selectedUserId', $admin->id)
            ->call('hubungkan')
            ->assertHasErrors(['selectedUserId']);
    }

    public function test_modal_secara_default_hanya_menampilkan_pengguna_tanpa_id_mesin(): void
    {
        $this->actingAsAdmin();

        $tanpaId = $this->makeSiswa('1001');
        $this->makeSiswa('1002', '7002');
        $this->tap('9001', '2026-10-27 06:15:00');

        $component = Livewire::test(RfidBelumTerdaftar::class)
            ->call('bukaAssign', '9001');

        $pengguna = $component->viewData('pengguna');

        $this->assertCount(1, $pengguna);
        $this->assertSame($tanpaId->user_id, $pengguna->first()->id);

        $semua = $component->set('hanyaTanpaId', false)->viewData('pengguna');
        $this->assertCount(2, $semua);
    }

    public function test_halaman_dapat_diakses_admin_dan_tautan_data_pengguna_membawa_rfid(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.rfid'))
            ->assertOk()
            ->assertSee('RFID Belum Terdaftar');

        $this->get(route('admin.users.create', ['rfid' => '9001']))
            ->assertOk()
            ->assertSee('Terisi dari halaman RFID Belum Terdaftar');
    }
}
