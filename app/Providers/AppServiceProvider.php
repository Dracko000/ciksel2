<?php

namespace App\Providers;

use App\Console\Commands\Aman\MigrateResetAman;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Perintah migrasi bawaan Laravel yang destructive (migrate:fresh,
        // migrate:refresh, migrate:reset, db:wipe) diganti dengan versi yang
        // menolak jalan di atas database produksi.
        //
        //
        // CATATAN: jangan pernah memproteksinya lewat
        // Event::listen(CommandStarting::class). Cara itu pernah dipakai di sini
        // dan ternyata dilewati begitu saja: ketika artisan dijalankan dengan
        // `--env=testing`, listener-nya tidak pernah dipanggil, sementara config
        // database tetap menunjuk ke "adms". Akibatnya seluruh isi database
        // sekolah terhapus tanpa terhalang. Pemeriksaan karena itu harus berada
        // di dalam kelas perintahnya sendiri, yang selalu terpakai.
        //
        // ResetCommand butuh Migrator yang di-inject, jadi harus di-bind manual.
        $this->app->singleton(MigrateResetAman::class, function ($app) {
            return new MigrateResetAman($app['migrator']);
        });
    }

    public function boot(): void
    {
        Paginator::useTailwind();
    }
}