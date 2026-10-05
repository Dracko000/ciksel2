<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\KonfirmasiIjin;
use App\Livewire\Admin\LaporanBulanan;
use App\Models\Attendance;
use App\Models\Kelas;
use App\Models\PengajuanIjin;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilterKelasLaporanTest extends TestCase
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

    private function makeSiswa(string $nis, ?Kelas $kelas, ?string $zktecoId = null): Siswa
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

    public function test_laporan_bulanan_bisa_difilter_per_kelas(): void
    {
        $this->actingAsAdmin();

        $kelasA = Kelas::create(['nama_kelas' => '4 A']);
        $kelasB = Kelas::create(['nama_kelas' => '4 B']);

        $this->makeSiswa('1001', $kelasA, '9001');
        $this->makeSiswa('1002', $kelasB, '9002');

        // Tanpa filter, laporan memuat seluruh siswa.
        $semua = Livewire::test(LaporanBulanan::class)->instance()->reportData;
        $this->assertCount(2, $semua);

        $component = Livewire::test(LaporanBulanan::class)
            ->set('kelasFilter', $kelasA->id)
            ->call('generateReport');

        $report = $component->instance()->reportData;

        $this->assertCount(1, $report);
        $this->assertSame('1001', $report[0]['nis']);
        $this->assertSame('4 A', $report[0]['kelas']);
    }

    public function test_laporan_bulanan_menyediakan_daftar_kelas(): void
    {
        $this->actingAsAdmin();

        Kelas::create(['nama_kelas' => '5 B']);
        Kelas::create(['nama_kelas' => '5 A']);

        $html = Livewire::test(LaporanBulanan::class)->html();

        $this->assertStringContainsString('5 A', $html);
        $this->assertStringContainsString('5 B', $html);

        // Kontrol filter harus benar-benar dirender, bukan hanya datanya.
        $this->assertStringContainsString('wire:model="kelasFilter"', $html);
        $this->assertStringContainsString('for="kelasFilter"', $html);
        $this->assertStringContainsString('Semua Kelas', $html);

        // Dropdown harus terurut nama kelas.
        $this->assertLessThan(
            strpos($html, '5 B'),
            strpos($html, '5 A'),
            'Daftar kelas pada filter harus urut berdasarkan nama.'
        );
    }

    public function test_konfirmasi_izin_bisa_difilter_per_kelas(): void
    {
        $this->actingAsAdmin();

        $kelasA = Kelas::create(['nama_kelas' => '6 A']);
        $kelasB = Kelas::create(['nama_kelas' => '6 B']);

        $siswaA = $this->makeSiswa('1001', $kelasA);
        $siswaB = $this->makeSiswa('1002', $kelasB);

        foreach ([$siswaA, $siswaB] as $siswa) {
            PengajuanIjin::create([
                'siswa_id' => $siswa->id,
                'jenis' => 'sakit',
                'keterangan' => 'Demam',
                'tanggal_mulai' => '2026-03-02',
                'tanggal_selesai' => '2026-03-02',
                'tahun_ajaran' => '2026/2027',
                'semester' => 'genap',
                'status' => 'pending',
            ]);
        }

        $component = Livewire::test(KonfirmasiIjin::class);

        $this->assertSame(2, $component->viewData('pengajuan')->total());

        $component->set('kelasFilter', $kelasA->id)
            ->assertSee('Siswa 1001')
            ->assertDontSee('Siswa 1002');

        $this->assertSame(1, $component->viewData('pengajuan')->total());
    }

    public function test_konfirmasi_izin_menampilkan_kolom_kelas(): void
    {
        $this->actingAsAdmin();

        $kelas = Kelas::create(['nama_kelas' => '6 C']);
        $siswa = $this->makeSiswa('1001', $kelas);

        PengajuanIjin::create([
            'siswa_id' => $siswa->id,
            'jenis' => 'izin',
            'keterangan' => 'Keperluan keluarga',
            'tanggal_mulai' => '2026-03-02',
            'tanggal_selesai' => '2026-03-02',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'genap',
            'status' => 'pending',
        ]);

        Livewire::test(KonfirmasiIjin::class)
            ->assertSee('6 C');

        $html = Livewire::test(KonfirmasiIjin::class)->html();

        $this->assertStringContainsString('wire:model.live="kelasFilter"', $html);
        $this->assertStringContainsString('for="kelasFilter"', $html);
        $this->assertStringContainsString('Semua Kelas', $html);

        // Pengajuan izin hanya berasal dari siswa, jadi filter role di sini
        // hanya akan menjadi dropdown satu opsi yang tidak berguna.
        $this->assertStringNotContainsString('roleFilter', $html);
    }

    public function test_reset_filter_konfirmasi_mengosongkan_kelas(): void
    {
        $this->actingAsAdmin();

        $kelas = Kelas::create(['nama_kelas' => '7 A']);
        $siswa = $this->makeSiswa('1001', $kelas);

        PengajuanIjin::create([
            'siswa_id' => $siswa->id,
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'tanggal_mulai' => '2026-03-02',
            'tanggal_selesai' => '2026-03-02',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'genap',
            'status' => 'pending',
        ]);

        Livewire::test(KonfirmasiIjin::class)
            ->set('kelasFilter', $kelas->id)
            ->call('resetFilter')
            ->assertSet('kelasFilter', '');
    }
}