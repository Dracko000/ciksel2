<?php

namespace Tests\Feature\Admin;

use App\Console\Commands\DeviceHeartbeat;
use App\Livewire\Admin\InputIjin as AdminInputIjin;
use App\Livewire\Admin\KonfirmasiIjin;
use App\Livewire\Ortu\InputIjin;
use App\Models\Device;
use App\Models\Ekstrakulikuler;
use App\Models\Guru;
use App\Models\PengajuanIjin;
use App\Models\Siswa;
use App\Models\User;
use App\Notifications\AdmsNotification;
use App\Notifications\DeviceOffline;
use App\Notifications\IzinDiajukan;
use App\Notifications\IzinDiputuskan;
use App\Notifications\JadwalMendatang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $username): User
    {
        return User::create([
            'name' => ucfirst($role).' Notif',
            'email' => $username.'@adms.local',
            'username' => $username,
            'password' => bcrypt('secret123'),
            'role' => $role,
        ]);
    }

    private function makeSiswa(?int $ortuUserId = null): Siswa
    {
        return Siswa::create([
            'user_id' => $this->makeUser('siswa', 'siswa-'.uniqid())->id,
            'ortu_user_id' => $ortuUserId,
            'nis' => '221099'.random_int(1000, 9999),
            'pin' => '221099'.random_int(1000, 9999),
            'nama' => 'Siswa Notif',
        ]);
    }

    private function buatIzin(int $siswaId, string $status = 'pending'): PengajuanIjin
    {
        return PengajuanIjin::create([
            'siswa_id' => $siswaId,
            'jenis' => 'izin',
            'keterangan' => 'Keperluan keluarga.',
            'tanggal_mulai' => '2026-10-07',
            'tanggal_selesai' => '2026-10-07',
            'status' => $status,
        ]);
    }

    public function test_pengajuan_izin_memperingatkan_admin(): void
    {
        Notification::fake();

        $admin = $this->makeUser('admin', 'admin-notif');
        $siswa = $this->makeSiswa();

        Livewire::actingAs($admin)
            ->test(AdminInputIjin::class)
            ->set('siswa_id', $siswa->id)
            ->set('jenis', 'sakit')
            ->set('keterangan', 'Demam tinggi sejak kemarin sore.')
            ->set('tanggal_mulai', '2026-10-07')
            ->set('tanggal_selesai', '2026-10-08')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, PengajuanIjin::where('siswa_id', $siswa->id)->count());
        Notification::assertSentTo($admin, IzinDiajukan::class);
    }

    public function test_pengajuan_izin_menolak_siswa_yang_tidak_ada(): void
    {
        Notification::fake();

        $admin = $this->makeUser('admin', 'admin-invalid');

        Livewire::actingAs($admin)
            ->test(AdminInputIjin::class)
            ->set('siswa_id', 999999)
            ->set('jenis', 'sakit')
            ->set('keterangan', 'Keterangan yang cukup panjang.')
            ->set('tanggal_mulai', '2026-10-07')
            ->set('tanggal_selesai', '2026-10-08')
            ->call('submit')
            ->assertHasErrors('siswa_id');

        Notification::assertNothingSent();
    }

    public function test_keputusan_izin_memberi_tahu_siswa(): void
    {
        Notification::fake();

        $siswa = $this->makeSiswa();
        $admin = $this->makeUser('admin', 'admin-keputusan');
        $ijin = $this->buatIzin($siswa->id);

        Livewire::actingAs($admin)
            ->test(KonfirmasiIjin::class)
            ->call('approve', $ijin->id);

        $this->assertSame('disetujui', $ijin->fresh()->status);
        Notification::assertSentTo($siswa->user, IzinDiputuskan::class);
    }

    public function test_penolakan_izin_juga_memberi_tahu_siswa(): void
    {
        Notification::fake();

        $siswa = $this->makeSiswa();
        $admin = $this->makeUser('admin', 'admin-tolak');
        $ijin = $this->buatIzin($siswa->id);

        Livewire::actingAs($admin)
            ->test(KonfirmasiIjin::class)
            ->call('reject', $ijin->id);

        $this->assertSame('ditolak', $ijin->fresh()->status);
        Notification::assertSentTo($siswa->user, IzinDiputuskan::class);
    }

    public function test_pengajuan_yang_sudah_diputuskan_tidak_kirim_ulang(): void
    {
        Notification::fake();

        $siswa = $this->makeSiswa();
        $admin = $this->makeUser('admin', 'admin-ulang');
        $ijin = $this->buatIzin($siswa->id, 'disetujui');

        Livewire::actingAs($admin)
            ->test(KonfirmasiIjin::class)
            ->call('reject', $ijin->id);

        $this->assertSame('disetujui', $ijin->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_payload_notifikasi_memuat_kunci_yang_dibaca_lonceng(): void
    {
        $siswa = $this->makeSiswa();
        $ijin = $this->buatIzin($siswa->id);

        $payload = (new IzinDiputuskan($ijin, 'disetujui'))->toDatabase($siswa->user);

        $this->assertSame('Izin disetujui', $payload['judul']);
        $this->assertStringContainsString('disetujui', $payload['pesan']);
        $this->assertSame('success', $payload['level']);
        $this->assertArrayHasKey('url', $payload);
    }

    public function test_notifikasi_hanya_memakai_kanal_database(): void
    {
        $notifikasi = new class extends AdmsNotification
        {
            public function toArray(object $notifiable): array
            {
                return $this->payload('Uji', 'Uji payload');
            }
        };

        $this->assertSame(['database'], $notifikasi->via($this->makeUser('admin', 'admin-kanal')));
    }

    public function test_device_lama_diam_ditandai_offline_dan_beri_tahu_admin(): void
    {
        Notification::fake();

        $admin = $this->makeUser('admin', 'admin-device');
        $device = Device::create([
            'nama' => 'Mesin Gerbang Utama',
            'no_sn' => 'SN-OFFLINE-TEST',
            'lokasi' => 'Gerbang Utama',
            'online' => now()->subMinutes(30),
            'last_seen_at' => now()->subMinutes(30),
        ]);

        $this->artisan('adms:device-heartbeat')->assertSuccessful();

        $this->assertNull($device->fresh()->online);
        Notification::assertSentTo($admin, DeviceOffline::class);
    }

    public function test_device_yang_baru_kontak_tidak_dianggap_offline(): void
    {
        Notification::fake();

        $this->makeUser('admin', 'admin-awet');
        $device = Device::create([
            'nama' => 'Mesin Segmentasi',
            'no_sn' => 'SN-AWET-TEST',
            'online' => now()->subMinutes(2),
            'last_seen_at' => now()->subMinutes(2),
        ]);

        $this->artisan('adms:device-heartbeat')->assertSuccessful();

        $this->assertNotNull($device->fresh()->online);
        Notification::assertNothingSent();
    }

    public function test_pengingat_jadwal_dikirim_ke_peserta_dan_pembina(): void
    {
        Notification::fake();

        // 5 Oktober 2026 adalah Senin, kolom "hari" hanya menerima Senin-Sabtu.
        Carbon::setTestNow(Carbon::parse('2026-10-05 06:00:00'));

        $admin = $this->makeUser('admin', 'admin-jadwal');
        $siswa = $this->makeSiswa();
        $guru = Guru::create([
            'user_id' => $this->makeUser('guru', 'pembina-'.uniqid())->id,
            'nip' => '19800101000'.random_int(100, 999),
            'pin' => '19800101000'.random_int(100, 999),
            'nama' => 'Pembina Ekskul',
        ]);

        $eks = Ekstrakulikuler::create([
            'nama' => 'Prakum Javascript',
            'guru_id' => $guru->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00:00',
            'aktif' => true,
        ]);
        $eks->siswa()->attach($siswa->id);

        $this->artisan('adms:device-heartbeat', ['--jadwal' => true])->assertSuccessful();

        Notification::assertSentTo($siswa->user, JadwalMendatang::class);
        Notification::assertSentTo($admin, JadwalMendatang::class);

        Carbon::setTestNow();
    }

    public function test_kegiatan_nonaktif_tidak_dikirim_pengingat(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::parse('2026-10-05 06:00:00'));

        $admin = $this->makeUser('admin', 'admin-nonaktif');
        $siswa = $this->makeSiswa();

        $eks = Ekstrakulikuler::create([
            'nama' => 'Ekstrakulikuler Libur',
            'hari' => 'Senin',
            'aktif' => false,
        ]);
        $eks->siswa()->attach($siswa->id);

        $this->artisan('adms:device-heartbeat', ['--jadwal' => true])->assertSuccessful();

        Notification::assertNotSentTo($siswa->user, JadwalMendatang::class);

        Carbon::setTestNow();
    }

    public function test_hari_minggu_dilewati_tanpa_error(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-04 06:00:00'));

        $this->assertNull(DeviceHeartbeat::namaHari());

        $this->artisan('adms:device-heartbeat', ['--jadwal' => true])->assertSuccessful();

        Carbon::setTestNow();
    }
}