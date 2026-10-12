<?php

namespace Tests\Feature\Admin;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unduh rekap absensi harian/mingguan/bulanan dalam bentuk CSV.
 *
 * Isi file harus sama dengan yang ditampilkan halaman Monitor Absensi,
 * karena keduanya dibangun dari AbsensiRekap yang sama.
 */
class AbsensiExportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@export.test',
            'username' => 'admin-export',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);
    }

    private function makeKelas(string $nama): Kelas
    {
        return Kelas::create(['nama_kelas' => $nama]);
    }

    private function makeSiswa(string $nis, Kelas $kelas, string $zktecoId): Siswa
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@export.test',
            'username' => $nis,
            'password' => bcrypt('secret'),
            'role' => 'siswa',
        ]);

        return Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => 'Siswa '.$nis,
            'kelas_id' => $kelas->id,
            'wajah_id_zkteco' => $zktecoId,
        ]);
    }

    private function makeGuru(string $nip, string $zktecoId): Guru
    {
        $user = User::create([
            'name' => 'Guru '.$nip,
            'email' => $nip.'@export.test',
            'username' => $nip,
            'password' => bcrypt('secret'),
            'role' => 'guru',
        ]);

        return Guru::create([
            'user_id' => $user->id,
            'nip' => $nip,
            'pin' => $nip,
            'nama' => 'Guru '.$nip,
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

    private function isiDataAbsensi(): array
    {
        $kelas = $this->makeKelas('4 B');
        $this->makeSiswa('1001', $kelas, '9001');
        $this->makeGuru('8001', '7001');

        $this->tap(9001, '2026-03-02 07:05:00');
        $this->tap(9001, '2026-03-02 15:00:00');
        $this->tap(7001, '2026-03-02 06:55:00');

        return [$kelas];
    }

    public function test_unduh_harian_mengembalikan_csv_dan_nama_file_per_tanggal(): void
    {
        $this->actingAsAdmin();
        $this->isiDataAbsensi();

        $response = $this->get(route('admin.absensi.download', [
            'periode' => 'harian',
            'tanggal' => '2026-03-02',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('absensi-harian_2026-03-02.csv', (string) $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();

        // Rekap harian: satu baris per orang, lengkap dengan jam masuk/pulang.
        $this->assertStringContainsString('Siswa 1001', $csv);
        $this->assertStringContainsString('07:05', $csv);
        $this->assertStringContainsString('15:00', $csv);
        $this->assertStringContainsString('Guru 8001', $csv);
        $this->assertStringContainsString('4 B', $csv);
        $this->assertStringContainsString('Jam Masuk', $csv);
    }

    public function test_unduh_mingguan_menampilkan_kolom_tanggal_dan_hari_hadir(): void
    {
        $this->actingAsAdmin();
        $this->isiDataAbsensi();

        $response = $this->get(route('admin.absensi.download', [
            'periode' => 'mingguan',
            'tanggal' => '2026-03-02',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('absensi-mingguan_2026-03-02_2026-03-08.csv', (string) $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Hari Hadir', $csv);
        $this->assertStringContainsString('07:05-15:00', $csv);
        // Senin 02-03-2026 adalah hari pertama minggu tersebut.
        $this->assertStringContainsString('Sen 02-03', $csv);
    }

    public function test_unduh_bulanan_menghitung_hari_hadir(): void
    {
        $this->actingAsAdmin();
        $this->isiDataAbsensi();

        $response = $this->get(route('admin.absensi.download', [
            'periode' => 'bulanan',
            'tanggal' => '2026-03-02',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('absensi-bulanan_2026-03.csv', (string) $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Periode', $csv);
        $this->assertStringContainsString('Maret 2026', $csv);
        $this->assertStringContainsString('Hari Hadir', $csv);
    }

    public function test_filter_kelas_dan_peran_dipakai_di_file(): void
    {
        $this->actingAsAdmin();
        $this->isiDataAbsensi();

        $kelas = Kelas::first();

        $csv = $this->get(route('admin.absensi.download', [
            'periode' => 'harian',
            'tanggal' => '2026-03-02',
            'kelas_id' => $kelas->id,
        ]))->streamedContent();

        $this->assertStringContainsString('Siswa 1001', $csv);
        $this->assertStringNotContainsString('Guru 8001', $csv);

        $csvSiswa = $this->get(route('admin.absensi.download', [
            'periode' => 'harian',
            'tanggal' => '2026-03-02',
            'peran' => 'siswa',
        ]))->streamedContent();

        $this->assertStringContainsString('Siswa 1001', $csvSiswa);
        $this->assertStringNotContainsString('Guru 8001', $csvSiswa);
    }

    public function test_unduh_presensi_guru_memakai_nama_file_dan_judul_khusus(): void
    {
        $this->actingAsAdmin();
        $this->isiDataAbsensi();

        $response = $this->get(route('admin.absensi.download', [
            'periode' => 'harian',
            'tanggal' => '2026-03-02',
            'peran' => 'guru',
        ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'presensi-guru-harian_2026-03-02.csv',
            (string) $response->headers->get('content-disposition')
        );

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Rekap Presensi Guru', $csv);
        $this->assertStringContainsString('Guru 8001', $csv);
        $this->assertStringNotContainsString('Siswa 1001', $csv);
    }

    public function test_tanpa_filter_periode_default_harian(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.absensi.download'));

        $response->assertOk();
        $this->assertStringContainsString('absensi-harian_'.date('Y-m-d').'.csv', (string) $response->headers->get('content-disposition'));
    }

    public function test_peran_lain_selain_siswa_dan_guru_ditolak(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.absensi.download', ['peran' => 'administrator']))->assertSessionHasErrors('peran');
    }

    public function test_pengunjung_non_admin_ditolak(): void
    {
        $guru = User::create([
            'name' => 'Guru Biasa',
            'email' => 'guru-biasa@export.test',
            'username' => 'guru-biasa',
            'password' => bcrypt('secret'),
            'role' => 'guru',
        ]);

        $this->actingAs($guru)
            ->get(route('admin.absensi.download'))
            ->assertForbidden();
    }
}
