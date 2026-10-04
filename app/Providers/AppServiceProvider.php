<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Perintah artisan yang menghapus seluruh isi database.
     */
    private const PERINTAH_DESTRUKTIF = [
        'migrate:fresh',
        'migrate:refresh',
        'migrate:reset',
        'db:wipe',
    ];

    /**
     * Nama database produksi yang menyimpan data sekolah sungguhan.
     */
    private const DATABASE_PRODUKSI = 'adms';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        $this->lindungiDatabaseProduksi();
    }

    /**
     * Hentikan perintah destruktif yang tidak sengaja diarahkan ke database produksi.
     *
     * Perintah ini membaca DB_DATABASE dari .env, bukan dari phpunit.xml, sehingga
     * `php artisan migrate:fresh` mudah tidak sengaja berjalan di atas data asli.
     * Karena itu perintah destruktif diblokir kecuali ADMS_ALLOW_DESTRUCTIVE=true.
     */
    private function lindungiDatabaseProduksi(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            $perintah = (string) $event->command;

            if (! in_array($perintah, self::PERINTAH_DESTRUKTIF, true)) {
                return;
            }

            if (filter_var(env('ADMS_ALLOW_DESTRUCTIVE', false), FILTER_VALIDATE_BOOLEAN)) {
                return;
            }

            $database = (string) config('database.connections.mysql.database');

            if ($database !== self::DATABASE_PRODUKSI) {
                return;
            }

            $output = $event->output;
            if ($output === null) {
                return;
            }

            $output->writeln('');
            $output->writeln(
                '<error>DIBATAS: perintah "'.$perintah.'" akan menghapus seluruh isi database "'.$database.'".</>'
            );
            $output->writeln(
                'Database ini berisi data sekolah sungguhan. Buat backup dulu dengan: php artisan adms:backup'
            );
            $output->writeln(
                'Jika benar-benar disengaja, jalankan dengan ADMS_ALLOW_DESTRUCTIVE=true'
            );
            $output->writeln('');

            exit(1);
        });
    }
}