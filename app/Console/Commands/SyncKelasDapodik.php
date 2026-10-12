<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Support\Roster\RosterExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Samakan `siswa.kelas_id` dengan data rombel pada file Dapodik
 * "Detail Sekolah - Data Pokok Pendidikan" (.xlsx).
 *
 * Data kelas di aplikasi pernah rusak (sebagian besar siswa menumpuk di satu
 * kelas), sedangkan file Dapodik memuat rombel yang benar per siswa. Perintah
 * ini mencocokkan tiap siswa di database dengan satu baris Excel memakai rantai
 * identifier, lalu memindahkan siswa ke kelas sesuai rombel baris tersebut.
 *
 * Rantai pencocokan (berhenti di yang pertama cocok):
 *   1. NIS siswa sama dengan NISN                         -> metode "nisn"
 *   2. NIS/PIN siswa -> NISN lewat file absensi (.xls)    -> metode "jembatan"
 *   3. NIS (5+ digit) sebagai akhiran unik sebuah NISN    -> metode "akhiran"
 *   4. nama siswa unik di file Excel                      -> metode "nama"
 *   5. nama ambigu, dibedakan lewat jembatan/absensi      -> metode "nama_jembatan"
 *
 * Siswa yang tidak cocok dan baris Excel yang tidak terpakai hanya dilaporkan,
 * tidak dibuat otomatis. Selalu jalankan --dry-run lebih dulu.
 */
class SyncKelasDapodik extends Command
{
    protected $signature = 'adms:sync-kelas
        {--file= : Path file Dapodik "Detail Sekolah" (.xlsx); dikosongkan berarti cari di public/}
        {--sumber= : Path file absensi (.xls) untuk jembatan NIS -> NISN; isi kosong untuk mematikan}
        {--detail : Tampilkan seluruh siswa yang berpindah kelas}
        {--dry-run : Tampilkan rencana tanpa menulis ke database}';

    protected $description = 'Samakan kelas siswa dengan data rombel pada file Dapodik Detail Sekolah';

    /**
     * Awalan nama file sumber bila opsi --file tidak diberikan.
     */
    private const FILE_DETAIL = 'detail sekolah';

    /**
     * Awalan nama file jembatan bila opsi --sumber tidak diberikan.
     */
    private const FILE_ABSENSI = 'absensi';

    public function handle(): int
    {
        $file = $this->option('file');
        if ($file === null) {
            $file = $this->temukan(public_path(), self::FILE_DETAIL);
        }

        if ($file === null || ! is_file($file)) {
            $this->error('File Detail Sekolah (.xlsx) tidak ditemukan. Tentukan lewat --file=PATH.');

            return self::FAILURE;
        }

        $excel = RosterExtractor::dariDetailSekolah($file);
        if ($excel === []) {
            $this->error('Tidak ada baris siswa yang terbaca. Pastikan file memuat kolom NISN dan Rombel.');

            return self::FAILURE;
        }

        $sumber = $this->option('sumber');
        $keteranganJembatan = 'dimatikan lewat --sumber';

        if ($sumber === null) {
            $sumber = $this->temukan(public_path(), self::FILE_ABSENSI);
            $keteranganJembatan = $sumber === null ? 'tidak ditemukan' : 'ditemukan otomatis';
        } elseif ($sumber !== '' && ! is_file($sumber)) {
            $this->error("File absensi jembatan tidak ditemukan: {$sumber}");

            return self::FAILURE;
        }

        if ($sumber === '') {
            $sumber = null;
        }

        $jembatan = $sumber !== null ? $this->bacaJembatan($sumber) : ['nis' => [], 'nama' => []];

        $this->line('Database : '.DB::connection()->getDatabaseName());
        $this->line('File     : '.basename($file).' ('.count($excel).' baris)');
        $this->line('Jembatan : '.($sumber !== null
            ? basename($sumber).' ('.count($jembatan['nis']).' NIS)'
            : $keteranganJembatan));
        $this->newLine();

        if (! $this->option('dry-run') && $this->databaseUji()) {
            $this->error('Perintah dihentikan: database tujuan adalah database pengujian ('
                .DB::connection()->getDatabaseName().').');
            $this->line('Jalankan dengan --dry-run atau arahkan ke database sekolah.');

            return self::FAILURE;
        }

        $hasil = $this->rencanakan($excel, $jembatan);

        $this->laporkan($hasil);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Mode dry-run: tidak ada perubahan yang disimpan.');

            return self::SUCCESS;
        }

        $dibuat = $this->terapkan($hasil);

        $this->newLine();
        $this->info("Selesai: {$hasil['berubah']} siswa dipindahkan, {$dibuat} kelas baru dibuat.");

        return self::SUCCESS;
    }

    /**
     * Bangun rencana perubahan dari data Excel + jembatan.
     *
     * @param  array<int, array{nama: string, nisn: string, jk: string, tingkat: string, rombel: string}>  $excel
     * @param  array{nis: array<string, string>, nama: array<string, string>}  $jembatan
     * @return array{
     *     siswa: array<int, array{id: int, nis: string, nama: string, dari: string, ke: string, berubah: bool}>,
     *     kelas_baru: array<int, string>,
     *     gagal: array<int, array{nis: string, nama: string, kelas: string}>,
     *     hanya_file: array<int, array{nama: string, nisn: string, rombel: string}>,
     *     sebelum: array<string, int>,
     *     sesudah: array<string, int>,
     *     metode: array<string, int>,
     *     berubah: int
     * }
     */
    private function rencanakan(array $excel, array $jembatan): array
    {
        // Indeks baris Excel.
        $byNisn = [];
        $byNama = [];
        foreach ($excel as $i => $row) {
            if ($row['nisn'] !== '') {
                $byNisn[$row['nisn']][] = $i;
            }
            $byNama[RosterExtractor::normalisasiNama($row['nama'])][] = $i;
        }

        // Indeks kelas database: kunci rombel (tanpa spasi) => model.
        $kelasByKunci = [];
        $kunciById = [];
        foreach (Kelas::query()->orderBy('id')->get() as $kelas) {
            $kunci = RosterExtractor::kunciRombel((string) $kelas->nama_kelas);
            $kelasByKunci[$kunci] ??= $kelas;
            $kunciById[$kelas->id] = $kunci;
        }

        $siswaDb = Siswa::query()->orderBy('id')->get();

        // Distribusi kelas sebelum perubahan.
        $sebelum = [];
        foreach ($siswaDb as $s) {
            $label = $this->labelKelas($s->kelas_id, $kunciById);
            $sebelum[$label] = ($sebelum[$label] ?? 0) + 1;
        }

        $metode = ['nisn' => 0, 'jembatan' => 0, 'akhiran' => 0, 'nama' => 0, 'nama_jembatan' => 0];
        $terpakai = [];
        $siswa = [];
        $gagal = [];
        $kelasBaru = [];

        foreach ($siswaDb as $s) {
            [$index, $cara] = $this->cocokkan($s, $excel, $byNisn, $byNama, $jembatan, $terpakai);

            if ($index === null) {
                $gagal[] = [
                    'nis' => (string) $s->nis,
                    'nama' => (string) $s->nama,
                    'kelas' => $this->labelKelas($s->kelas_id, $kunciById),
                ];

                continue;
            }

            $terpakai[$index] = true;
            $metode[$cara]++;

            $rombel = $excel[$index]['rombel'];
            $kelasTujuan = $kelasByKunci[$rombel] ?? null;
            $berubah = $kelasTujuan === null || (int) $s->kelas_id !== (int) $kelasTujuan->id;

            if ($kelasTujuan === null) {
                $kelasBaru[$rombel] = true;
            }

            $siswa[] = [
                'id' => (int) $s->id,
                'nis' => (string) $s->nis,
                'nama' => (string) $s->nama,
                'dari' => $this->labelKelas($s->kelas_id, $kunciById),
                'ke' => $rombel,
                'berubah' => $berubah,
            ];
        }

        // Baris Excel yang tidak dipakai siswa mana pun.
        $hanyaFile = [];
        foreach ($excel as $i => $row) {
            if (! isset($terpakai[$i])) {
                $hanyaFile[] = [
                    'nama' => $row['nama'],
                    'nisn' => $row['nisn'],
                    'rombel' => $row['rombel'],
                ];
            }
        }

        // Distribusi kelas sesudah perubahan.
        $sesudah = $sebelum;
        $berubah = 0;
        foreach ($siswa as $row) {
            if (! $row['berubah']) {
                continue;
            }

            $sesudah[$row['dari']] = ($sesudah[$row['dari']] ?? 0) - 1;
            $sesudah[$row['ke']] = ($sesudah[$row['ke']] ?? 0) + 1;
            $berubah++;
        }

        return [
            'siswa' => $siswa,
            'kelas_baru' => array_keys($kelasBaru),
            'gagal' => $gagal,
            'hanya_file' => $hanyaFile,
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
            'metode' => $metode,
            'berubah' => $berubah,
        ];
    }

    /**
     * Cari satu baris Excel untuk seorang siswa tanpa merebut baris yang sudah dipakai.
     *
     * @param  array<int, array{nama: string, nisn: string, rombel: string}>  $excel
     * @param  array<string, array<int, int>>  $byNisn
     * @param  array<string, array<int, int>>  $byNama
     * @param  array{nis: array<string, string>, nama: array<string, string>}  $jembatan
     * @param  array<int, bool>  $terpakai
     * @return array{0: int|null, 1: string|null}
     */
    private function cocokkan(Siswa $siswa, array $excel, array $byNisn, array $byNama, array $jembatan, array $terpakai): array
    {
        $nis = RosterExtractor::bersihNisn((string) $siswa->nis);
        $nama = RosterExtractor::normalisasiNama((string) $siswa->nama);

        $nisnJembatan = null;
        if ($nis !== '' && isset($jembatan['nis'][$nis])) {
            $nisnJembatan = $jembatan['nis'][$nis];
        } else {
            $pin = RosterExtractor::bersihNisn((string) $siswa->pin);
            if ($pin !== '' && isset($jembatan['nis'][$pin])) {
                $nisnJembatan = $jembatan['nis'][$pin];
            }
        }

        // 1. NIS sama dengan NISN.
        if (($i = $this->pilih($byNisn[$nis] ?? [], $terpakai)) !== null) {
            return [$i, 'nisn'];
        }

        // 2. Jembatan NIS/PIN -> NISN dari file absensi.
        if ($nisnJembatan !== null && ($i = $this->pilih($byNisn[$nisnJembatan] ?? [], $terpakai)) !== null) {
            return [$i, 'jembatan'];
        }

        // 3. NIS sebagai akhiran unik sebuah NISN (NIS lama 5-6 digit).
        if (strlen($nis) >= 5) {
            $kandidat = [];
            foreach ($byNisn as $nisn => $indeks) {
                if (! str_ends_with($nisn, $nis)) {
                    continue;
                }

                foreach ($this->semua($indeks, $terpakai) as $i) {
                    $kandidat[] = $i;
                }
            }

            if (count($kandidat) === 1) {
                return [$kandidat[0], 'akhiran'];
            }
        }

        // 4/5. Cocokkan lewat nama.
        $kandidat = $this->semua($byNama[$nama] ?? [], $terpakai);

        if (count($kandidat) === 1) {
            return [$kandidat[0], 'nama'];
        }

        if (count($kandidat) > 1) {
            // 5a. Nama ambigu: pilih baris yang NISN-nya sesuai jembatan.
            if ($nisnJembatan !== null) {
                foreach ($kandidat as $i) {
                    if ($excel[$i]['nisn'] === $nisnJembatan) {
                        return [$i, 'nama_jembatan'];
                    }
                }
            }

            // 5b. Nama ambigu: pilih baris yang NISN-nya sesuai nama di file absensi.
            $nisnNama = $jembatan['nama'][$nama] ?? null;
            if ($nisnNama !== null && $nisnNama !== '') {
                foreach ($kandidat as $i) {
                    if ($excel[$i]['nisn'] === $nisnNama) {
                        return [$i, 'nama_jembatan'];
                    }
                }
            }
        }

        return [null, null];
    }

    /**
     * Terapkan rencana ke database secara transaksional.
     *
     * @param  array{siswa: array<int, array{id: int, ke: string, berubah: bool}>, kelas_baru: array<int, string>}  $hasil
     * @return int jumlah kelas baru yang dibuat
     */
    private function terapkan(array $hasil): int
    {
        return DB::transaction(function () use ($hasil): int {
            $kelasByKunci = [];
            foreach (Kelas::query()->orderBy('id')->get() as $kelas) {
                $kunci = RosterExtractor::kunciRombel((string) $kelas->nama_kelas);
                $kelasByKunci[$kunci] ??= $kelas;
            }

            $dibuat = 0;
            foreach ($hasil['kelas_baru'] as $kunci) {
                if (! isset($kelasByKunci[$kunci])) {
                    $kelasByKunci[$kunci] = Kelas::create(['nama_kelas' => $kunci]);
                    $dibuat++;
                }
            }

            foreach ($hasil['siswa'] as $row) {
                if (! $row['berubah'] || ! isset($kelasByKunci[$row['ke']])) {
                    continue;
                }

                Siswa::query()->whereKey($row['id'])->update([
                    'kelas_id' => $kelasByKunci[$row['ke']]->id,
                ]);
            }

            return $dibuat;
        });
    }

    /**
     * Bangun peta NIS/PIN => NISN dan nama => NISN dari file absensi.
     *
     * @return array{nis: array<string, string>, nama: array<string, string>}
     */
    private function bacaJembatan(string $path): array
    {
        $roster = RosterExtractor::dariAbsensi($path);

        $nis = [];
        $nama = [];

        foreach ($roster['siswa'] as $row) {
            $nisn = RosterExtractor::bersihNisn((string) $row['nisn']);
            if ($nisn === '') {
                continue;
            }

            foreach ([$row['nis'], $row['pin']] as $kunci) {
                $kunci = RosterExtractor::bersihNisn((string) $kunci);
                if ($kunci !== '' && ! isset($nis[$kunci])) {
                    $nis[$kunci] = $nisn;
                }
            }

            $namaKunci = RosterExtractor::normalisasiNama((string) $row['nama']);
            if ($namaKunci === '') {
                continue;
            }

            // Nama ganda di file absensi dibiarkan ambigu (nilai kosong).
            if (isset($nama[$namaKunci]) && $nama[$namaKunci] !== $nisn) {
                $nama[$namaKunci] = '';
            } elseif (! isset($nama[$namaKunci])) {
                $nama[$namaKunci] = $nisn;
            }
        }

        return ['nis' => $nis, 'nama' => $nama];
    }

    /**
     * Cetak laporan rencana ke layar.
     *
     * @param  array<string, mixed>  $hasil
     */
    private function laporkan(array $hasil): void
    {
        $metode = [
            'NIS = NISN' => $hasil['metode']['nisn'],
            'Jembatan NIS/PIN -> NISN' => $hasil['metode']['jembatan'],
            'Akhiran NISN unik' => $hasil['metode']['akhiran'],
            'Nama unik' => $hasil['metode']['nama'],
            'Nama + jembatan' => $hasil['metode']['nama_jembatan'],
        ];

        $this->table(['Metode pencocokan', 'Jumlah'], $this->barisTabel($metode));

        $this->table(['Status', 'Jumlah'], [
            ['Berubah kelas', $hasil['berubah']],
            ['Tidak berubah', count($hasil['siswa']) - $hasil['berubah']],
            ['Gagal dicocokkan', count($hasil['gagal'])],
            ['Hanya di file', count($hasil['hanya_file'])],
            ['Kelas baru', count($hasil['kelas_baru'])],
        ]);

        $this->table(['Kelas', 'Sebelum', 'Sesudah'], $this->distribusi($hasil));

        if ($hasil['gagal'] !== []) {
            $this->warn('Gagal dicocokkan (kelas dibiarkan):');
            foreach (array_slice($hasil['gagal'], 0, 15) as $row) {
                $this->line("  - {$row['nis']} {$row['nama']} (sekarang {$row['kelas']})");
            }
            if (count($hasil['gagal']) > 15) {
                $this->line('  ... dan '.(count($hasil['gagal']) - 15).' lainnya');
            }
        }

        if ($hasil['hanya_file'] !== []) {
            $this->warn('Hanya ada di file (tidak dibuat):');
            foreach (array_slice($hasil['hanya_file'], 0, 15) as $row) {
                $this->line("  - {$row['nama']} ({$row['nisn']}) -> {$row['rombel']}");
            }
            if (count($hasil['hanya_file']) > 15) {
                $this->line('  ... dan '.(count($hasil['hanya_file']) - 15).' lainnya');
            }
        }

        $berubah = array_values(array_filter($hasil['siswa'], static fn (array $row): bool => $row['berubah']));
        if ($berubah !== []) {
            $semua = (bool) $this->option('detail');
            $batas = $semua ? count($berubah) : min(10, count($berubah));

            $this->line('Perubahan kelas'.($semua ? '' : ' (10 pertama)').':');
            foreach (array_slice($berubah, 0, $batas) as $row) {
                $this->line("  - {$row['nis']} {$row['nama']}: {$row['dari']} -> {$row['ke']}");
            }
            if (count($berubah) > $batas) {
                $this->line('  ... dan '.(count($berubah) - $batas).' lainnya (pakai --detail untuk semua)');
            }
        }
    }

    /**
     * @param  array<string, int>  $hasil
     * @return array<int, array{0: string, 1: int}>
     */
    private function barisTabel(array $hasil): array
    {
        $rows = [];
        foreach ($hasil as $label => $jumlah) {
            $rows[] = [$label, $jumlah];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $hasil
     * @return array<int, array{0: string, 1: int, 2: int}>
     */
    private function distribusi(array $hasil): array
    {
        $kunci = array_unique(array_merge(array_keys($hasil['sebelum']), array_keys($hasil['sesudah'])));
        sort($kunci);

        $rows = [];
        foreach ($kunci as $label) {
            $rows[] = [$label, $hasil['sebelum'][$label] ?? 0, $hasil['sesudah'][$label] ?? 0];
        }

        return $rows;
    }

    /**
     * @param  array<int, int>  $indeks
     * @param  array<int, bool>  $terpakai
     */
    private function pilih(array $indeks, array $terpakai): ?int
    {
        foreach ($indeks as $i) {
            if (! isset($terpakai[$i])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  array<int, int>  $indeks
     * @param  array<int, bool>  $terpakai
     * @return array<int, int>
     */
    private function semua(array $indeks, array $terpakai): array
    {
        return array_values(array_filter($indeks, static fn (int $i): bool => ! isset($terpakai[$i])));
    }

    /**
     * @param  array<int, string>  $kunciById
     */
    private function labelKelas(?int $kelasId, array $kunciById): string
    {
        if ($kelasId === null) {
            return '(tanpa kelas)';
        }

        return $kunciById[$kelasId] ?? "(#{$kelasId})";
    }

    /**
     * Cari file berdasarkan awalan di $dir, termasuk satu tingkat subdirektori.
     */
    private function temukan(string $dir, string $awalan): ?string
    {
        $awalan = Str::lower($awalan);
        $kandidat = [];

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = rtrim($dir, '/').'/'.$entry;

            if (is_file($path)) {
                if (str_starts_with(Str::lower($entry), $awalan)) {
                    $kandidat[] = $path;
                }

                continue;
            }

            if (! is_dir($path)) {
                continue;
            }

            foreach (scandir($path) ?: [] as $child) {
                if ($child === '.' || $child === '..') {
                    continue;
                }

                $childPath = $path.'/'.$child;
                if (is_file($childPath) && str_starts_with(Str::lower($child), $awalan)) {
                    $kandidat[] = $childPath;
                }
            }
        }

        // Pilih file terbaru supaya hasil stabil saat ada beberapa ekspor.
        usort($kandidat, static function (string $a, string $b): int {
            return (filemtime($b) <=> filemtime($a)) ?: strcmp($a, $b);
        });

        return $kandidat[0] ?? null;
    }

    /**
     * Cegah penulisan tidak sengaja ke database pengujian saat dijalankan manual.
     */
    private function databaseUji(): bool
    {
        $database = (string) DB::connection()->getDatabaseName();

        return in_array($database, ['adms_testing'], true) && ! app()->runningUnitTests();
    }
}
