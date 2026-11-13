<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\DeviceManagement;
use App\Models\Device;
use App\Models\DeviceCommand;
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

        Livewire::test(DeviceManagement::class)
            ->call('bukaPerintah', $device->id)
            ->assertSet('perintahDeviceId', $device->id)
            ->set('perintahJenis', 'hapus_user')
            ->set('perintahPin', '2')
            ->set('konfirmasi', $device->no_sn)
            ->call('kirimPerintah')
            ->assertHasNoErrors()
            ->assertSet('konfirmasi', '')
            ->assertSet('perintahPin', '');

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

        Livewire::test(DeviceManagement::class)
            ->call('bukaPerintah', $device->id)
            ->set('perintahJenis', 'hapus_log')
            ->set('konfirmasi', 'SN-SALAH')
            ->call('kirimPerintah')
            ->assertHasErrors(['konfirmasi']);

        $this->assertSame(0, DeviceCommand::count());
    }

    public function test_pin_tidak_valid_ditolak(): void
    {
        $this->actingAsAdmin();
        $device = $this->makeDevice();

        Livewire::test(DeviceManagement::class)
            ->call('bukaPerintah', $device->id)
            ->set('perintahJenis', 'hapus_user')
            ->set('perintahPin', '2 PIN=3')
            ->set('konfirmasi', $device->no_sn)
            ->call('kirimPerintah')
            ->assertHasErrors(['perintah']);

        $this->assertSame(0, DeviceCommand::count());
    }

    public function test_pin_kosong_ditolak(): void
    {
        $this->actingAsAdmin();
        $device = $this->makeDevice();

        Livewire::test(DeviceManagement::class)
            ->call('bukaPerintah', $device->id)
            ->set('perintahJenis', 'hapus_user')
            ->set('perintahPin', '')
            ->set('konfirmasi', $device->no_sn)
            ->call('kirimPerintah')
            ->assertHasErrors(['perintah']);

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
}
