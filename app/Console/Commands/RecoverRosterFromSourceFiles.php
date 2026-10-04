<?php

namespace App\Console\Commands;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Roster\RosterExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Rebuild data sekolah (kelas, siswa, guru/tendik, user) dari file sumber Dapodik.
 *
 * Perintah ini idempoten: dijalankan berulang kali akan memperbarui baris yang
 * ada, bukan membuat duplikat. Jalankan dengan --dry-run lebih dulu.
 */
class RecoverRosterFromSourceFiles extends Command
{
    protected $signature = 'adms:recover-roster
        {--source= : Folder yang berisi file absensi .xls dan daftar guru/tendik .xlsx}
        {--dry-run : Tampilkan rencana tanpa menulis ke database}
        {--skip-extra-siswa : Jangan tambahkan siswa yang hanya ada di DB lama, bukan di file}';

    protected $description = 'Pulihkan data kelas, siswa, guru dari file sumber Dapodik (password = username)';

    /** Siswa yang ada di DB lama tapi tidak ada di file absensi. */
    private const SISWA_EKSTRA = [
        ['pin' => '252601115', 'nama' => 'Azharudin Hazim Nazair', 'kelas' => '2 A'],
    ];

    private const ADMIN_USERNAME = 'administrator';

    public function handle(): int
    {
        $source = $this->option('source')
            ?: env('ADMS_ROSTER_SOURCE')
            ?: 'C:/Users/USER/Downloads/data ciksel 2';

        if (! is_dir($source)) {
            $this->error("Folder sumber tidak ditemukan: {$source}");

            return self::FAILURE;
        }

        $absensi = $this->findFile($source, 'absensi', ['xls', 'xlsx']);
        $guruFile = $this->findFile($source, 'daftar-guru', ['xlsx']);
        $tendikFile = $this->findFile($source, 'daftar-tendik', ['xlsx']);

        foreach (compact('absensi', 'guruFile', 'tendikFile') as $label => $path) {
            if ($path === null) {
                $this->error("File '{$label}' tidak ditemukan di {$source}");

                return self::FAILURE;
            }
        }

        $roster = RosterExtractor::dariAbsensi($absensi);
        $ptk = array_merge(
            RosterExtractor::dariDaftarPtk($guruFile, false),
            RosterExtractor::dariDaftarPtk($tendikFile, true),
        );

        $this->line("Sumber   : {$source}");
        $this->line("Absensi  : " . basename($absensi));
        $this->line("Guru     : " . basename($guruFile) . ' (' . count(RosterExtractor::dariDaftarPtk($guruFile, false)) . ' orang)');
        $this->line("Tendik   : " . basename($tendikFile) . ' (' . count(RosterExtractor::dariDaftarPtk($tendikFile, true)) . ' orang)');
        $this->newLine();

        $identifierPtk = [];
        foreach ($ptk as $position => $row) {
            $identifierPtk[$position] = $this->identifierPtk($row);
        }

        $tanpaId = [];
        foreach ($identifierPtk as $position => $identifier) {
            if ($identifier === null) {
                $tanpaId[] = $ptk[$position]['nama'];
            }
        }

        $siswa = $roster['siswa'];
        if (! $this->option('skip-extra-siswa')) {
            foreach (self::SISWA_EKSTRA as $extra) {
                $siswa[] = [
                    'nama' => $extra['nama'],
                    'kelas' => $extra['kelas'],
                    'jk' => '',
                    'nisn' => '',
                    'nis' => $extra['pin'],
                    'pin' => $extra['pin'],
                ];
            }
        }

        $indeksWali = RosterExtractor::indeksWali($ptk);
        $waliTidakKetemu = [];
        foreach ($roster['kelas'] as $kelas) {
            if ($kelas['wali'] !== null && ! isset($indeksWali[Str::lower(trim($kelas['wali']))])) {
                $waliTidakKetemu[] = $kelas['nama'].' -> '.$kelas['wali'];
            }
        }

        $this->table(
            ['Entitas', 'Jumlah'],
            [
                ['Kelas', count($roster['kelas'])],
                ['Siswa (file)', count($roster['siswa'])],
                ['Siswa tambahan (dari DB lama)', $this->option('skip-extra-siswa') ? 0 : count(self::SISWA_EKSTRA)],
                ['Guru', count(array_filter($ptk, fn ($p) => ! $p['is_tendik']))],
                ['Tendik', count(array_filter($ptk, fn ($p) => $p['is_tendik']))],
                ['Total PTK', count($ptk)],
            ],
        );

        $siswaTanpaNis = count(array_filter($roster['siswa'], fn ($s) => $s['nis'] === ''));
        $this->line("Siswa tanpa NIS di file  : {$siswaTanpaNis} (kolom nis diisi dengan NISN)");
        $this->line("Duplikat identifier       : " . count($roster['duplikat']));
        $this->line("Sel tanda absensi terisi  : {$roster['sel_absensi']}");

        if ($tanpaId !== []) {
            $this->warn('PTK tanpa NIP maupun NUPTK (akan dilewati): ' . implode(', ', array_slice($tanpaId, 0, 10)));
        }
        if ($waliTidakKetemu !== []) {
            $this->warn('Wali kelas tidak ditemukan di daftar PTK:');
            foreach (array_slice($waliTidakKetemu, 0, 30) as $line) {
                $this->line("    {$line}");
            }
        }
        $lewatNuptk = [];
        $lewatNik = [];
        foreach ($ptk as $position => $row) {
            if (trim($row['nip']) !== '' || $identifierPtk[$position] === null) {
                continue;
            }
            if (trim($row['nuptk']) !== '') {
                $lewatNuptk[] = $row['nama'].' ('.$row['nuptk'].')';
            } else {
                $lewatNik[] = $row['nama'].' ('.$row['nik'].')';
            }
        }
        if ($lewatNuptk !== []) {
            $this->warn('PTK tanpa NIP, identifier = NUPTK:');
            foreach ($lewatNuptk as $item) {
                $this->line("    {$item}");
            }
        }
        if ($lewatNik !== []) {
            $this->warn('PTK tanpa NIP dan NUPTK, identifier = NIK:');
            foreach ($lewatNik as $item) {
                $this->line("    {$item}");
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Mode dry-run. Tidak ada perubahan ke database.');

            return self::SUCCESS;
        }

        $stats = DB::transaction(function () use ($ptk, $identifierPtk, $siswa, $roster, $indeksWali) {
            $stats = ['user' => 0, 'guru' => 0, 'kelas' => 0, 'siswa' => 0];

            $this->upsertUser(self::ADMIN_USERNAME, 'Administrator', 'admin');
            $stats['user']++;

            $guruIds = [];
            foreach ($ptk as $position => $row) {
                $identifier = $identifierPtk[$position];
                if ($identifier === null) {
                    continue;
                }

                $user = $this->upsertUser($identifier, $row['nama'], 'guru');

                $guru = Guru::firstOrNew(['nip' => $identifier]);
                $guru->user_id = $user->id;
                $guru->nama = $row['nama'];
                $guru->pin = $identifier;
                $guru->is_tendik = $row['is_tendik'];
                $guru->save();

                $guruIds[$position] = $guru->id;
                $stats['guru']++;
                $stats['user']++;
            }

            $kelasIds = [];
            foreach ($roster['kelas'] as $row) {
                $waliPosition = isset($indeksWali[Str::lower(trim((string) $row['wali']))])
                    ? $indeksWali[Str::lower(trim((string) $row['wali']))]
                    : null;

                $kelas = Kelas::firstOrNew(['nama_kelas' => $row['nama']]);
                $kelas->wali_kelas_id = $waliPosition !== null ? ($guruIds[$waliPosition] ?? null) : null;
                $kelas->save();

                $kelasIds[$row['nama']] = $kelas->id;
                $stats['kelas']++;
            }

            foreach ($siswa as $row) {
                $user = $this->upsertUser($row['pin'], $row['nama'], 'siswa');

                $siswa = Siswa::firstOrNew(['nis' => $row['pin']]);
                $siswa->user_id = $user->id;
                $siswa->nama = $row['nama'];
                $siswa->pin = $row['pin'];
                $siswa->kelas_id = $kelasIds[$row['kelas']] ?? null;
                $siswa->save();

                $stats['siswa']++;
                $stats['user']++;
            }

            return $stats;
        });

        $this->newLine();
        $this->info('Rebuild selesai:');
        $this->table(['Entitas', 'Ditulis'], [
            ['User', $stats['user']],
            ['Guru/Tendik', $stats['guru']],
            ['Kelas', $stats['kelas']],
            ['Siswa', $stats['siswa']],
        ]);
        $this->line('Password semua akun = username. Admin: ' . self::ADMIN_USERNAME . ' / ' . self::ADMIN_USERNAME);

        return self::SUCCESS;
    }

    private function upsertUser(string $identifier, string $name, string $role): User
    {
        $user = User::firstOrNew(['username' => $identifier]);
        $user->name = $name;
        $user->email = $identifier.'@adms.local';
        $user->password = Hash::make($identifier);
        $user->role = $role;
        $user->save();

        return $user;
    }

    /**
     * Rantai identifier untuk PTK: NIP, lalu NUPTK, lalu NIK.
     *
     * NIP dipakai pertama karena itu identifier PNS/PPKG. Guru honor sekolah
     * sering tidak punya NIP maupun NUPTK di Dapodik, sehingga memakai NIK
     * sebagai identifier terakhir supaya tetap bisa login dan absen.
     *
     * @param  array{nama: string, nuptk: string, nip: string, nik: string}  $row
     */
    private function identifierPtk(array $row): ?string
    {
        foreach (['nip', 'nuptk', 'nik'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Cari file berdasarkan.awalan, mengabaikan besar kecil huruf.
     *
     * @param  array<int, string>  $extensions
     */
    private function findFile(string $dir, string $needle, array $extensions): ?string
    {
        $needle = strtolower($needle);
        $allowed = array_map('strtolower', $extensions);

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = rtrim($dir, '/').'/'.$entry;
            if (! is_file($path)) {
                continue;
            }

            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (! in_array($extension, $allowed, true)) {
                continue;
            }

            if (str_starts_with(strtolower($entry), $needle)) {
                return $path;
            }
        }

        return null;
    }
}