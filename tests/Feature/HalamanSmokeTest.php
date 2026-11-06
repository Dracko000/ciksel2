<?php

namespace Tests\Feature;

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
            ->assertSee(config('app.school_name'));
    }

    public function test_halaman_admin_semua_dapat_dirender(): void
    {
        $admin = $this->buatUser('admin');

        foreach ([
            '/admin/dashboard',
            '/admin/kelas',
            '/admin/ekstrakulikuler',
            '/admin/users',
            '/admin/users/create',
            '/admin/rfid-belum-terdaftar',
            '/admin/devices',
            '/admin/absensi',
            '/admin/presensi-guru',
            '/admin/presensi-murid',
            '/admin/jam-guru',
            '/admin/jam-kelas',
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

    /** Keputusan desain: sidebar navy gelap, kanvas abu muda, dan aksen
     *  merah sekolah dipakai hemat untuk aksi utama serta menu aktif. */
    public function test_sidebar_gelap_dan_aksen_tetap_bermerek(): void
    {
        $admin = $this->buatUser('admin');
        $html = $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->getContent();

        // Sidebar gelap + item aktif bermerek.
        $this->assertStringContainsString('bg-navy-900', $html, 'Sidebar harus memakai latar navy gelap.');
        $this->assertStringContainsString('sidebar-link-active', $html, 'Menu aktif harus ditandai.');

        $this->assertStringNotContainsString('card bg-brand-600', $html, 'Kartu bantuan teknis tidak boleh merah pekat.');

        foreach (['Kehadiran Hari Ini', 'Hadir', 'Tidak Hadir', 'Total Siswa', 'Kehadiran Guru &amp; Tendik Hari Ini', 'Total Guru &amp; Tendik'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        // Aksen brand tetap dipakai untuk tautan dan status.
        $this->assertStringContainsString('text-brand-600', $html);
        $this->assertStringNotContainsString('rainbow-bar', $html, 'Strip pelangi lama sudah diganti aksen tipis.');
    }

    /** Keputusan desain: kanvas lembut + kepala tabel netral, bukan blok
     *  gradien merah yang mendominasi setiap halaman. */
    public function test_kanvas_lembut_dan_header_tabel_netral(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('--color-canvas: #f6f7fb', $css, 'Kanvas halaman memakai abu sangat muda.');
        $this->assertMatchesRegularExpression('/\.table thead \{[^}]*bg-slate-50/', $css, 'Header tabel harus netral.');
        $this->assertMatchesRegularExpression('/\.table tbody tr \{[^}]*bg-white/', $css, 'Baris tabel harus putih.');
        $this->assertMatchesRegularExpression('/\.table th \{[^}]*text-slate-500/', $css, 'Teks header kolom harus abu gelap.');
        $this->assertStringNotContainsString('maroon', $css, 'Skema maroon gelap tidak boleh tersisa di stylesheet.');
        $this->assertMatchesRegularExpression('/\.sidebar-link \{[^}]*text-slate-400/', $css, 'Sidebar harus memakai teks terang di atas navy.');
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
