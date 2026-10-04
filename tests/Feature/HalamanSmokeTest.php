<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MenEnsure setiap halaman benar-benar bisa dirender.
 *
 * Test lain hanya memanggil method Livewire sehingga error compile pada view
 * yang tidak tersentuh bisa lolos. Test ini visiting setiap route sebagai user
 * sungguhan supaya Blade, komponen, dan layout ikut dievaluasi.
 */
class HalamanSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_tampil_untuk_tamu(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk ke akun Anda', escape: false)
            ->assertSee('SIAKAD');
    }

    public function test_halaman_admin_semua_dapat_dirender(): void
    {
        $admin = $this->buatUser('admin');

        foreach ([
            '/admin/dashboard',
            '/admin/kelas',
            '/admin/ekstrakulikuler',
            '/admin/users',
            '/admin/devices',
            '/admin/absensi',
            '/admin/informasi',
            '/admin/laporan',
            '/admin/konfirmasi',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_halaman_guru_dapat_dirender(): void
    {
        $guru = $this->buatUser('guru');

        foreach (['/guru/dashboard', '/guru/absensi', '/guru/nilai'] as $url) {
            $this->actingAs($guru)->get($url)->assertOk();
        }
    }

    public function test_halaman_siswa_dapat_dirender(): void
    {
        $user = $this->buatUser('siswa');
        $user->siswa()->create([
            'user_id' => $user->id,
            'nis' => '2210900001',
            'pin' => '2210900001',
            'nama' => 'Siswa Uji',
        ]);

        // Orang tua memakai akun anaknya, jadi form pengajuan izin ikut di sini.
        foreach (['/siswa/dashboard', '/siswa/izin'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_admin_tidak_bisa_membuka_halaman_siswa(): void
    {
        $admin = $this->buatUser('admin');

        $this->actingAs($admin)->get('/siswa/izin')->assertForbidden();
    }

    public function test_nav_sidebar_siswa_menampilkan_pengajuan_izin(): void
    {
        $user = $this->buatUser('siswa');
        $user->siswa()->create([
            'user_id' => $user->id,
            'nis' => '2210900003',
            'pin' => '2210900003',
            'nama' => 'Siswa Nav',
        ]);

        $this->actingAs($user)->get('/siswa/dashboard')
            ->assertSee('Pengajuan Izin')
            ->assertDontSee('Management Kelas');
    }

    public function test_nav_sidebar_hanya_menampilkan_menu_sesuai_peran(): void
    {
        $admin = $this->buatUser('admin');
        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertSee('Management Kelas')
            ->assertSee('Ekstrakulikuler')
            ->assertDontSee('Input Nilai');

        $guru = $this->buatUser('guru');
        $this->actingAs($guru)->get('/guru/dashboard')
            ->assertSee('Input Nilai')
            ->assertDontSee('Management Kelas');
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    private function buatUser(string $role): User
    {
        return User::create([
            'name' => 'Uji '.$role,
            'email' => $role.'-smoke@adms.local',
            'username' => $role.'-smoke',
            'password' => bcrypt('secret123'),
            'role' => $role,
        ]);
    }
}