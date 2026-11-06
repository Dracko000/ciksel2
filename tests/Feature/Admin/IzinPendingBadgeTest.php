<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\IzinPendingBadge;
use App\Livewire\Admin\KonfirmasiIjin;
use App\Models\PengajuanIjin;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Angka pengajuan izin yang menunggu konfirmasi admin.
 *
 * Badge muncul di samping menu "Konfirmasi Izin" selama masih ada pengajuan
 * berstatus "pending", dan hilang setelah semuanya diputuskan.
 */
class IzinPendingBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $username): User
    {
        return User::create([
            'name' => ucfirst($role).' Izin',
            'email' => $username.'@adms.local',
            'username' => $username,
            'password' => bcrypt('secret123'),
            'role' => $role,
        ]);
    }

    private function makeSiswa(): Siswa
    {
        return Siswa::create([
            'user_id' => $this->makeUser('siswa', 'siswa-'.uniqid())->id,
            'nis' => '221099'.random_int(1000, 9999),
            'pin' => '221099'.random_int(1000, 9999),
            'nama' => 'Siswa Izin',
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

    public function test_angka_sesuai_jumlah_pengajuan_pending(): void
    {
        $admin = $this->makeUser('admin', 'admin-badge');
        $siswa = $this->makeSiswa();

        $this->buatIzin($siswa->id, 'pending');
        $this->buatIzin($siswa->id, 'pending');
        $this->buatIzin($siswa->id, 'disetujui');

        Livewire::actingAs($admin)
            ->test(IzinPendingBadge::class)
            ->assertSet('jumlah', 2)
            ->assertSeeHtml('>2</span>');
    }

    public function test_angka_kosong_bila_semua_sudah_diputuskan(): void
    {
        $admin = $this->makeUser('admin', 'admin-kosong');
        $siswa = $this->makeSiswa();

        $this->buatIzin($siswa->id, 'disetujui');
        $this->buatIzin($siswa->id, 'ditolak');

        Livewire::actingAs($admin)
            ->test(IzinPendingBadge::class)
            ->assertSet('jumlah', 0)
            ->assertSeeHtml('data-testid="izin-pending-badge"');
    }

    public function test_bukan_admin_tidak_menampilkan_angka(): void
    {
        $siswa = $this->makeSiswa();
        $this->buatIzin($siswa->id, 'pending');

        Livewire::actingAs($siswa->user)
            ->test(IzinPendingBadge::class)
            ->assertSet('jumlah', 0);
    }

    public function test_angka_menyegar_setelah_izin_dikonfirmasi(): void
    {
        $admin = $this->makeUser('admin', 'admin-segar');
        $siswa = $this->makeSiswa();
        $ijin = $this->buatIzin($siswa->id, 'pending');

        $komponen = Livewire::actingAs($admin)
            ->test(IzinPendingBadge::class)
            ->assertSet('jumlah', 1);

        // Simulasi admin memutuskan pengajuan di halaman lain: status berubah,
        // lalu event 'izin-dikonfirmasi' menyegarkan angka di sidebar.
        $ijin->update(['status' => 'disetujui']);

        $komponen->dispatch('izin-dikonfirmasi')
            ->assertSet('jumlah', 0);
    }

    public function test_halaman_konfirmasi_memancarkan_event_setelah_diputuskan(): void
    {
        // Notifikasi asli dipalsukan supaya test hanya fokus pada event badge.
        Notification::fake();

        $admin = $this->makeUser('admin', 'admin-event');
        $siswa = $this->makeSiswa();
        $ijin = $this->buatIzin($siswa->id, 'pending');

        Livewire::actingAs($admin)
            ->test(KonfirmasiIjin::class)
            ->call('approve', $ijin->id)
            ->assertDispatched('izin-dikonfirmasi');
    }

    public function test_sidebar_admin_menampilkan_badge(): void
    {
        $admin = $this->makeUser('admin', 'admin-sidebar');
        $siswa = $this->makeSiswa();
        $this->buatIzin($siswa->id, 'pending');

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Konfirmasi Izin')
            ->assertSee('data-testid="izin-pending-badge"', escape: false);
    }

    public function test_sidebar_non_admin_tidak_menampilkan_badge(): void
    {
        $siswa = $this->makeSiswa();

        $this->actingAs($siswa->user)
            ->get('/siswa/dashboard')
            ->assertOk()
            ->assertDontSee('data-testid="izin-pending-badge"', escape: false);
    }
}
