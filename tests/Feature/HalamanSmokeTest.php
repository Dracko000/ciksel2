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
            ->assertSee('ADMS');
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
            '/admin/presensi-guru',
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

    /** Keputusan desain: kanvas putih bersih, warna muncul sebagai aksen
     *  (gradien, pelangi, chip ikon) — bukan kanvas gelap dan bukan merah
     *  pekat di mana-mana. Merah penuh tetap hanya untuk aksi utama,
     *  posisi aktif, dan status. */
    public function test_latar_halaman_putih_dan_aksen_tetap_bermerek(): void
    {
        $admin = $this->buatUser('admin');
        $html = $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->getContent();

        // Header tabel tidak boleh jadi blok netral tanpa merek.
        $this->assertStringNotContainsString('bg-slate-50" ', $html, 'Header tabel tidak boleh netral.');
        $this->assertStringNotContainsString('card bg-brand-600', $html, 'Kartu bantuan teknis tidak boleh merah pekat.');

        foreach (['Kehadiran Hari Ini', 'Hadir', 'Tidak Hadir', 'Total Siswa', 'Izin Menunggu', 'Mesin Online'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        // Aksen brand dan sentuhan warna tetap dipertahankan.
        $this->assertStringContainsString('text-brand-700', $html);
        $this->assertStringContainsString('rainbow-bar', $html, 'Strip pelangi menjadi penanda aksen berwarna.');
    }

    /** Keputusan desain: kanvas putih bersih + aksen berwarna, menggantikan
     *  tema maroon gelap lama. */
    public function test_kanvas_putih_dan_header_tabel_gradien(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('--color-canvas: #ffffff', $css, 'Kanvas halaman harus putih bersih.');
        $this->assertMatchesRegularExpression('/\.table thead \{[^}]*from-brand-600/', $css, 'Header tabel harus gradien brand.');
        $this->assertMatchesRegularExpression('/\.table tbody tr \{[^}]*bg-white/', $css, 'Baris tabel harus putih.');
        $this->assertMatchesRegularExpression('/\.table th \{[^}]*text-white/', $css, 'Teks header kolom harus putih di atas gradien.');
        $this->assertStringNotContainsString('maroon', $css, 'Skema maroon gelap tidak boleh tersisa di stylesheet.');
        $this->assertStringContainsString('.rainbow-bar', $css, 'Aksen berwarna tetap ada di stylesheet.');
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