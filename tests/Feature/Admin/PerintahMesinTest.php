<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\PerintahMesinPage;
use App\Models\Attendance;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Absensi\PerintahMesin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Perintah ke mesin ZKTeco lewat push-protocol.
 *
 * Mesin mengambil perintah sendiri di /iclock/getrequest lalu melapor balik
 * di /iclock/devicecmd. Test ini memastikan antrean, pengiriman, laporan
 * balik, dan pengamanan halaman admin bekerja.
 */
class PerintahMesinTest extends TestCase
{
    use RefreshDatabase;

    private function makeDevice(string $sn = 'SN-UJI-001', string $nama = 'Mesin Uji'): Device
    {
        return Device::create([
            'nama' => $nama,
            'no_sn' => $sn,
            'lokasi' => 'Ruang Guru',
        ]);
    }

    private function actingAsAdmin(): User
    {
        $admin = User::create([
            'name' => 'Admin Mesin',
            'email' => 'admin.mesin@sekolah.test',
            'username' => 'admin_mesin',
            'password' => bcrypt('secret1234'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    public function test_mesin_menerima_perintah_hapus_user_yang_menunggu(): void
    {
        $device = $this->makeDevice();

        $cmd = PerintahMesin::hapusUser($device, '2');

        $response = $this->get('/iclock/getrequest?SN='.$device->no_sn);

        $response->assertOk();
        $this->assertSame('C:'.$cmd->id.':DATA DELETE USERINFO PIN=2', trim($response->getContent()));

        $cmd->refresh();
        $this->assertSame('sent', $cmd->status);
        $this->assertNotNull($cmd->sent_at);
    }

    public function test_getrequest_balas_ok_bila_tidak_ada_perintah(): void
    {
        $device = $this->makeDevice();

        $this->get('/iclock/getrequest?SN='.$device->no_sn)
            ->assertOk()
            ->assertSee('OK');
    }

    public function test_perintah_milik_mesin_lain_tidak_terkirim(): void
    {
        $mesinA = $this->makeDevice('SN-A', 'Mesin A');
        $mesinB = $this->makeDevice('SN-B', 'Mesin B');

        PerintahMesin::hapusUser($mesinB, '9');

        $this->get('/iclock/getrequest?SN='.$mesinA->no_sn)
            ->assertOk()
            ->assertSee('OK');

        $this->assertSame(0, DeviceCommand::untukMesin($mesinA->no_sn)->count());
    }

    public function test_laporan_balik_menandai_perintah_berhasil(): void
    {
        $device = $this->makeDevice();
        $cmd = PerintahMesin::hapusUser($device, '2');

        $this->post('/iclock/devicecmd?SN='.$device->no_sn, [
            'ID' => $cmd->id,
            'Return' => '0',
            'CMD' => 'DATA DELETE USERINFO PIN=2',
        ])->assertOk();

        $cmd->refresh();

        $this->assertSame('done', $cmd->status);
        $this->assertSame('0', $cmd->return_code);
        $this->assertNotNull($cmd->finished_at);
    }

    public function test_laporan_balik_gagal_ditandai_failed(): void
    {
        $device = $this->makeDevice();
        $cmd = PerintahMesin::hapusUser($device, '2');

        $this->post('/iclock/devicecmd?SN='.$device->no_sn, [
            'ID' => $cmd->id,
            'Return' => '-1',
        ])->assertOk();

        $this->assertSame('failed', $cmd->fresh()->status);
    }

    public function test_admin_mengantre_hapus_user_dari_halaman_device(): void
    {
        $this->actingAsAdmin();
        $device = $this->makeDevice();

        Livewire::test(PerintahMesinPage::class)
            ->call('pilihDevice', $device->id)
            ->assertSet('deviceId', $device->id)
            ->set('jenis', 'hapus_user')
            ->set('pin', '2')
            ->set('konfirmasi', $device->no_sn)
            ->call('kirim')
            ->assertHasNoErrors()
            ->assertSet('konfirmasi', '')
            ->assertSet('pin', '');

        $this->assertDatabaseHas('device_commands', [
            'no_sn' => $device->no_sn,
            'command' => 'DATA DELETE USERINFO PIN=2',
            'status' => 'pending',
        ]);
    }

    public function test_konfirmasi_sn_salah_menolak_perintah(): void
    {
        $this->actingAsAdmin();
        $device = $this->makeDevice();

        Livewire::test(PerintahMesinPage::class)
            ->call('pilihDevice', $device->id)
            ->set('jenis', 'hapus_log')
            ->set('konfirmasi', 'SN-SALAH')
            ->call('kirim')
            ->assertHasErrors(['konfirmasi']);

        $this->assertSame(0, DeviceCommand::count());
    }

    public function test_pin_tidak_valid_ditolak(): void
    {
        $this->actingAsAdmin();
        $device = $this->makeDevice();

        Livewire::test(PerintahMesinPage::class)
            ->call('pilihDevice', $device->id)
            ->set('jenis', 'hapus_user')
            ->set('pin', '2 PIN=3')
            ->set('konfirmasi', $device->no_sn)
            ->call('kirim')
            ->assertHasErrors(['jenis']);

        $this->assertSame(0, DeviceCommand::count());
    }

    public function test_pin_kosong_ditolak(): void
    {
        $this->actingAsAdmin();
        $device = $this->makeDevice();

        Livewire::test(PerintahMesinPage::class)
            ->call('pilihDevice', $device->id)
            ->set('jenis', 'hapus_user')
            ->set('pin', '')
            ->set('konfirmasi', $device->no_sn)
            ->call('kirim')
            ->assertHasErrors(['jenis']);

        $this->assertSame(0, DeviceCommand::count());
    }

    public function test_hapus_log_dan_reboot_memakai_perintah_baku(): void
    {
        $device = $this->makeDevice();

        PerintahMesin::hapusLog($device);
        PerintahMesin::reboot($device);

        $this->assertSame(
            ['CLEAR LOG', 'REBOOT'],
            DeviceCommand::untukMesin($device->no_sn)->orderBy('id')->pluck('command')->all()
        );
    }

    public function test_bukan_admin_tidak_bisa_membuka_halaman_device(): void
    {
        $guru = User::create([
            'name' => 'Guru Biasa',
            'email' => 'guru.mesin@sekolah.test',
            'username' => 'guru_mesin',
            'password' => bcrypt('secret123'),
            'role' => 'guru',
        ]);

        $this->actingAs($guru)
            ->get('/admin/devices')
            ->assertForbidden();
    }

    public function test_halaman_perintah_mesin_menampilkan_mesin_dan_riwayat(): void
    {
        $this->actingAsAdmin();
        $device = $this->makeDevice();
        PerintahMesin::hapusUser($device, '2');

        $this->get(route('admin.perintah-mesin'))
            ->assertOk()
            ->assertSee('Perintah Mesin')
            ->assertSee($device->no_sn)
            ->assertSee('DATA DELETE USERINFO PIN=2');
    }

    public function test_query_device_memilih_mesin_yang_ditautkan(): void
    {
        $this->actingAsAdmin();
        $this->makeDevice('SN-A', 'Mesin A');
        $kedua = $this->makeDevice('SN-B', 'Mesin B');

        Livewire::withQueryParams(['device' => $kedua->id])
            ->test(PerintahMesinPage::class)
            ->assertSet('deviceId', $kedua->id);
    }

    public function test_daftar_pengguna_hanya_dari_tap_mesin_terpilih(): void
    {
        $this->actingAsAdmin();
        $mesinA = $this->makeDevice('SN-A', 'Mesin A');
        $this->makeDevice('SN-B', 'Mesin B');

        $user = User::create([
            'name' => 'Siswa Tap A',
            'email' => 'tap.a@sekolah.test',
            'username' => '2210900777',
            'password' => bcrypt('secret123'),
            'role' => 'siswa',
        ]);

        Siswa::create([
            'user_id' => $user->id,
            'nis' => '2210900777',
            'pin' => '2210900777',
            'nama' => 'Siswa Tap A',
            'wajah_id_zkteco' => '777',
        ]);

        // Tap di mesin A.
        Attendance::create([
            'sn' => $mesinA->no_sn,
            'table' => 'ATTLOG',
            'stamp' => '1',
            'employee_id' => '777',
            'timestamp' => '2026-11-10 07:00:00',
        ]);

        // Tap di mesin B.
        Attendance::create([
            'sn' => 'SN-B',
            'table' => 'ATTLOG',
            'stamp' => '1',
            'employee_id' => '888',
            'timestamp' => '2026-11-10 07:00:00',
        ]);

        Livewire::test(PerintahMesinPage::class)
            ->call('pilihDevice', $mesinA->id)
            ->assertSee('Siswa Tap A')
            ->assertSee('777')
            ->assertDontSee('888');
    }

    public function test_pilih_pengguna_mengisi_pin(): void
    {
        $this->actingAsAdmin();
        $device = $this->makeDevice();

        Livewire::test(PerintahMesinPage::class)
            ->call('pilihDevice', $device->id)
            ->call('pilihPengguna', '242501007')
            ->assertSet('pin', '242501007');
    }

    public function test_filter_status_riwayat(): void
    {
        $this->actingAsAdmin();
        $device = $this->makeDevice();

        $selesai = PerintahMesin::hapusUser($device, '1');
        $selesai->update(['status' => 'done', 'return_code' => '0']);
        PerintahMesin::hapusUser($device, '2');

        Livewire::test(PerintahMesinPage::class)
            ->call('pilihDevice', $device->id)
            ->set('statusFilter', 'done')
            ->assertSee('DATA DELETE USERINFO PIN=1')
            ->assertDontSee('DATA DELETE USERINFO PIN=2');
    }
}
