<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Membuat backup SQL database sekolah.
 *
 * Backup disimpan di luar repository agar tidak ikut ter-commit ke Git.
 */
class BackupDatabase extends Command
{
    protected $signature = 'adms:backup
        {--output= : Folder tujuan, default dari ADMS_BACKUP_PATH atau folder adms-backups di Desktop}
        {--keep= : Jumlah backup lama yang dipertahankan, default 14}';

    protected $description = 'Backup database ADMS ke file SQL';

    public function handle(): int
    {
        $database = (string) config('database.connections.mysql.database');
        $output = $this->option('output')
            ?: env('ADMS_BACKUP_PATH')
            ?: $this->defaultDirectory();

        if (! is_dir($output) && ! @mkdir($output, 0777, true) && ! is_dir($output)) {
            $this->error("Gagal membuat folder backup: {$output}");

            return self::FAILURE;
        }

        $dump = $this->findBinary('mysqldump');
        if ($dump === null) {
            $this->error('mysqldump tidak ditemukan. Tambahkan folder MySQL ke PATH atau set MYSQL_BIN_PATH.');

            return self::FAILURE;
        }

        $path = rtrim($output, '/').'/'.$database.'-'.date('Ymd-His').'.sql';
        $partial = $path.'.partial';

        $process = new Process(array_merge([$dump, '-u', (string) config('database.connections.mysql.username')], [
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--default-character-set=utf8mb4',
            $database,
        ]));
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful() || $process->getOutput() === '') {
            @unlink($partial);
            $this->error('mysqldump gagal: '.trim($process->getErrorOutput()));

            return self::FAILURE;
        }

        // Tulis lewat file sementara supaya dump yang berhasil tidak pernah
        // menimpa file backup yang sudah ada.
        if (file_put_contents($partial, $process->getOutput()) === false || ! rename($partial, $path)) {
            @unlink($partial);
            $this->error("Gagal menyimpan backup ke {$path}");

            return self::FAILURE;
        }

        $tables = DB::selectOne('SELECT COUNT(*) AS jumlah FROM information_schema.tables WHERE table_schema = ?', [$database]);
        $rows = DB::table('users')->count();

        $this->info('Backup berhasil: '.$path);
        $this->line('  ukuran  : '.number_format(filesize($path)).' bytes');
        $this->line('  tables  : '.($tables->jumlah ?? 0));
        $this->line('  users   : '.$rows);

        $this->bersihkan($output, (int) $this->option('keep'), $database);

        return self::SUCCESS;
    }

    private function defaultDirectory(): string
    {
        $home = getenv('USERPROFILE') ?: getenv('HOME');

        return rtrim((string) $home, '/\\').'/Desktop/adms-backups';
    }

    /**
     * @return string|null
     */
    private function findBinary(string $name): ?string
    {
        $paths = array_filter([
            env('MYSQL_BIN_PATH'),
            'C:/xampp/mysql/bin',
            '/c/xampp/mysql/bin',
            '/usr/local/mysql/bin',
            '/usr/bin',
        ]);

        foreach ($paths as $directory) {
            foreach ([$name, $name.'.exe', $name.'.bat'] as $candidate) {
                $path = rtrim((string) $directory, '/\\').DIRECTORY_SEPARATOR.$candidate;
                if (is_file($path)) {
                    return $path;
                }
            }
        }

        $which = @shell_exec('where '.escapeshellarg($name).' 2>nul');

        return is_string($which) && trim($which) !== '' ? trim(explode("\n", $which)[0]) : null;
    }

    private function bersihkan(string $directory, int $keep, string $database): void
    {
        if ($keep < 1) {
            return;
        }

        $files = glob(rtrim($directory, '/').'/'.$database.'-*.sql') ?: [];
        rsort($files);

        foreach (array_slice($files, $keep) as $file) {
            if (@unlink($file)) {
                $this->line('  dihapus: '.basename($file));
            }
        }
    }
}