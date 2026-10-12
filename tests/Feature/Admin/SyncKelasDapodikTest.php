<?php

namespace Tests\Feature\Admin;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/**
 * Uji perintah adms:sync-kelas: menyamakan kelas siswa dengan rombel pada file
 * Dapodik "Detail Sekolah".
 *
 * Fixture dibuat langsung di setUp (xlsx inline-string + .xls SpreadsheetML)
 * supaya test tidak bergantung pada file data sekolah sungguhan. Rancangan
 * datanya sengaja memaksa tiap cabang rantai pencocokan:
 *   - Budi         : NIS sama NISN (dan sudah di kelas yang benar)
 *   - 2x Muhammad  : nama ganda, dibedakan lewat jembatan NIS -> NISN
 *   - Azharudin    : hanya cocok lewat nama unik
 *   - Siti Aminah  : NIS 6 digit sebagai akhiran unik NISN
 *   - Tono         : pindah ke rombel "5 D" yang belum ada kelasnya
 *   - Rina         : tidak ada di file (gagal dicocokkan)
 *   - 2x Aisyah    : hanya ada di file, tidak dibuat
 */
class SyncKelasDapodikTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    private string $file;

    private string $sumber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/sync-kelas-'.uniqid();
        mkdir($this->dir);

        $this->file = $this->dir.'/Detail Sekolah - Data Pokok Pendidikan.xlsx';
        $this->sumber = $this->dir.'/Absensi - SDN uji.xls';

        $this->buatXlsx($this->file, [
            ['Nama Lengkap', 'L/P', 'NISN', 'Tingkat', 'Rombel'],
            ['Budi Santoso', 'L', "\u{200C}3160010001", 'Kelas 1', '1 A'],
            ['MUHAMMAD ILHAM MAULANA', 'L', '3166937514', 'Kelas 4', '4 B'],
            ['MUHAMMAD ILHAM MAULANA', 'L', '3141258499', 'Kelas 6', '6 B'],
            ['AZHARUDIN', 'L', '3130002222', 'Kelas 2', '2 A'],
            ['Siti Aminah', 'P', '3190061697', 'Kelas 3', '3 A'],
            ['Tono', 'L', '3110005555', 'Kelas 5', '5 D'],
            ['Aisyah Albina Putri', 'P', '3152765468', 'Kelas 4', '4 D'],
            ['Aisyah Albina Putri', 'P', '3152765468', 'Kelas 4', '4 B'],
        ]);

        // Jembatan hanya memuat pasangan NIS -> NISN untuk kedua Muhammad.
        $this->buatXls($this->sumber, [
            '1. 4 B' => [
                [1, '3166937514/232401092', 'MUHAMMAD ILHAM MAULANA', 'L'],
            ],
            '1. 6 B' => [
                [1, '3141258499/212201021', 'MUHAMMAD ILHAM MAULANA', 'L'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        @unlink($this->sumber);
        @rmdir($this->dir);

        parent::tearDown();
    }

    public function test_dry_run_menampilkan_rencana_tanpa_mengubah_database(): void
    {
        $this->isiDatabase();

        $sebelum = Siswa::orderBy('id')->pluck('kelas_id', 'nis')->all();

        $this->artisan('adms:sync-kelas', [
            '--file' => $this->file,
            '--sumber' => $this->sumber,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Mode dry-run')
            ->assertExitCode(0);

        $this->assertSame($sebelum, Siswa::orderBy('id')->pluck('kelas_id', 'nis')->all());
        $this->assertDatabaseMissing('kelas', ['nama_kelas' => '5D']);
    }

    public function test_menyamakan_kelas_siswa_sesuai_rombel_dapodik(): void
    {
        $this->isiDatabase();

        $this->artisan('adms:sync-kelas', [
            '--file' => $this->file,
            '--sumber' => $this->sumber,
        ])
            ->expectsOutputToContain('5 siswa dipindahkan, 1 kelas baru dibuat')
            ->assertExitCode(0);

        // Nama ganda dibedakan lewat jembatan.
        $this->assertSame($this->kelasId('4B'), $this->kelasSiswa('232401092'));
        $this->assertSame($this->kelasId('6B'), $this->kelasSiswa('212201021'));

        // Nama unik dan akhiran NISN.
        $this->assertSame($this->kelasId('2A'), $this->kelasSiswa('252601115'));
        $this->assertSame($this->kelasId('3A'), $this->kelasSiswa('061697'));

        // Kelas baru dibuat untuk rombel "5 D".
        $this->assertDatabaseHas('kelas', ['nama_kelas' => '5D']);
        $this->assertSame($this->kelasId('5D'), $this->kelasSiswa('3110005555'));

        // Sudah benar tetap di tempatnya; yang gagal tidak disentuh.
        $this->assertSame($this->kelasId('1A'), $this->kelasSiswa('3160010001'));
        $this->assertSame($this->kelasId('1C'), $this->kelasSiswa('999999'));

        // Aisyah hanya ada di file, tidak dibuat menjadi siswa.
        $this->assertDatabaseMissing('siswa', ['nama' => 'Aisyah Albina Putri']);
    }

    public function test_tanpa_jembatan_nama_ganda_gagal_dicocokkan(): void
    {
        $this->isiDatabase();

        $this->artisan('adms:sync-kelas', [
            '--file' => $this->file,
            '--sumber' => '',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Gagal dicocokkan')
            ->expectsOutputToContain('Muhammad Ilham Maulana')
            ->assertExitCode(0);

        // Tanpa jembatan, kedua Muhammad tetap di kelas lama.
        $this->assertSame($this->kelasId('1C'), $this->kelasSiswa('232401092'));
        $this->assertSame($this->kelasId('1C'), $this->kelasSiswa('212201021'));
    }

    public function test_dijalankan_ulang_tidak_menambah_perubahan(): void
    {
        $this->isiDatabase();

        $this->artisan('adms:sync-kelas', [
            '--file' => $this->file,
            '--sumber' => $this->sumber,
        ])->assertExitCode(0);

        $sesudah = Siswa::orderBy('id')->pluck('kelas_id', 'nis')->all();

        $this->artisan('adms:sync-kelas', [
            '--file' => $this->file,
            '--sumber' => $this->sumber,
        ])
            ->expectsOutputToContain('0 siswa dipindahkan, 0 kelas baru dibuat')
            ->assertExitCode(0);

        $this->assertSame($sesudah, Siswa::orderBy('id')->pluck('kelas_id', 'nis')->all());
    }

    public function test_file_sumber_tidak_ditemukan_mengembalikan_gagal(): void
    {
        $this->artisan('adms:sync-kelas', [
            '--file' => $this->dir.'/tidak-ada.xlsx',
        ])
            ->expectsOutputToContain('tidak ditemukan')
            ->assertExitCode(1);
    }

    /**
     * Siapkan kelas dan siswa awal sesuai rancangan fixture.
     */
    private function isiDatabase(): void
    {
        $kelas = [];
        foreach (['1A', '1C', '2A', '3A', '4B', '6B'] as $nama) {
            $kelas[$nama] = Kelas::create(['nama_kelas' => $nama]);
        }

        $this->buatSiswa('3160010001', 'Budi Santoso', $kelas['1A']);
        $this->buatSiswa('232401092', 'Muhammad Ilham Maulana', $kelas['1C']);
        $this->buatSiswa('212201021', 'Muhammad Ilham Maulana', $kelas['1C']);
        $this->buatSiswa('252601115', 'Azharudin', $kelas['1C']);
        $this->buatSiswa('061697', 'Siti Aminah', $kelas['1C']);
        $this->buatSiswa('3110005555', 'Tono', $kelas['1C']);
        $this->buatSiswa('999999', 'Rina', $kelas['1C']);
    }

    private function buatSiswa(string $nis, string $nama, Kelas $kelas): Siswa
    {
        $user = User::create([
            'name' => $nama,
            'email' => $nis.'@sync-kelas.test',
            'username' => 'u'.$nis,
            'password' => bcrypt('secret'),
            'role' => 'siswa',
        ]);

        return Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => $nama,
            'kelas_id' => $kelas->id,
        ]);
    }

    private function kelasId(string $nama): int
    {
        return (int) Kelas::where('nama_kelas', $nama)->value('id');
    }

    private function kelasSiswa(string $nis): ?int
    {
        $kelasId = Siswa::where('nis', $nis)->value('kelas_id');

        return $kelasId === null ? null : (int) $kelasId;
    }

    /**
     * Tulis file .xlsx minimal (sheet1 dengan sel inlineStr).
     *
     * @param  array<int, array<int, string>>  $rows
     */
    private function buatXlsx(string $path, array $rows): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $r => $row) {
            $xml .= '<row r="'.($r + 1).'">';
            foreach ($row as $ci => $value) {
                $kolom = chr(65 + $ci);
                $xml .= '<c r="'.$kolom.($r + 1).'" t="inlineStr"><is><t>'
                    .htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                    .'</t></is></c>';
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();
    }

    /**
     * Tulis file .xls SpreadsheetML minimal.
     *
     * @param  array<string, array<int, array<int, string|int>>>  $sheets
     */
    private function buatXls(string $path, array $sheets): void
    {
        $xml = '<?xml version="1.0"?>';
        $xml .= '<Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';

        foreach ($sheets as $nama => $rows) {
            $xml .= '<Worksheet ss:Name="'.htmlspecialchars($nama, ENT_XML1 | ENT_QUOTES, 'UTF-8').'"><Table>';
            foreach ($rows as $row) {
                $xml .= '<Row>';
                foreach ($row as $value) {
                    $xml .= '<Cell><Data ss:Type="String">'
                        .htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                        .'</Data></Cell>';
                }
                $xml .= '</Row>';
            }
            $xml .= '</Table></Worksheet>';
        }

        $xml .= '</Workbook>';

        file_put_contents($path, $xml);
    }
}
