<?php

namespace App\Console\Commands;

/**
 * Menghentikan perintah artisan yang menghapus data sebelum data hilang.
 *
 * Versi pertama proteksi ini memakai Event::listen(CommandStarting::class) di
 * dalam AppServiceProvider. Cara itu ternyata bisa dilewati: saat artisan
 * dijalankan dengan `--env=testing`, listener tersebut tidak pernah dipanggil
 * sama sekali (sudah dibuktikan lewat log), padahal config database tetap
 * menunjuk ke "adms". Akibatnya `migrate:fresh --env=testing` menghapus
 * seluruh isi database produksi tanpa terhalang.
 *
 * Karena itu pemeriksaan dipindahkan ke dalam kelas perintahnya sendiri, lewat
 * ConfirmableTrait::confirmToProceed() yang dipanggil setiap kali perintah
 * destruktif akan berjalan. Nama database yang diperiksa adalah database yang
 * benar-benar ditulis, bukan sekadar nilai config.
 */
trait PeriksaDatabaseProduksi
{
    /**
     * Environment variable yang mengizinkan penghapusan di atas database itu.
     */
    private const ENVIZIN = 'ADMS_ALLOW_DESTRUCTIVE';

    /**
     * Environment variable berisi nama database yang dilindungi.
     *
     * Bisa diisi lebih dari satu, dipisah koma. Dibuat configurable supaya
     * proteksinya bisa diuji tanpa menyentuh database sungguhan.
     */
    private const ENVDATABASE = 'ADMS_PRODUCTION_DATABASE';

    /**
     * Nama database yang benar-benar akan ditulis oleh perintah ini.
     */
    protected function databaseYangDitujuan(): string
    {
        $koneksi = $this->input->getOption('database') ?: config('database.default');

        // Opsi --database bisa berisi nama koneksi (mis. mysql) atau nama database.
        if (config("database.connections.{$koneksi}.database") !== null) {
            return (string) config("database.connections.{$koneksi}.database");
        }

        return (string) $koneksi;
    }

    /**
     * Daftar nama database yang tidak boleh dikosongkan.
     *
     * @return list<string>
     */
    protected function namaDatabaseTidakBolehDihapus(): array
    {
        $nilai = env(self::ENVDATABASE) ?: getenv(self::ENVDATABASE) ?: 'adms';

        $daftar = array_values(array_filter(array_map(
            static fn ($item) => trim($item),
            explode(',', (string) $nilai)
        )));

        return $daftar === [] ? ['adms'] : $daftar;
    }

    /**
     * Dipanggil framework melalui ConfirmableTrait, tepat sebelum data dihapus.
     *
     * Mengembalikan false akan menghentikan perintah sepenuhnya.
     */
    public function confirmToProceed($warning = 'Application In Production!', $callback = null)
    {
        $database = $this->databaseYangDitujuan();

        if (! in_array($database, $this->namaDatabaseTidakBolehDihapus(), true)) {
            return parent::confirmToProceed($warning, $callback);
        }

        if ($this->izinDestructive()) {
            $this->components->warn("Database \"{$database}\" akan DIHAPUS.");

            return parent::confirmToProceed($warning, $callback);
        }

        $this->components->error("Perintah dibatalkan: database \"{$database}\" berisi data sekolah sungguhan.");
        $this->newLine();
        $this->line('  Buat backup dulu       : php artisan adms:backup');
        $this->line('  Untuk database testing : php artisan migrate:fresh --database=adms_testing --force');
        $this->line('  Benar-benar sengaja    : --force '.self::ENVIZIN.'=true');
        $this->newLine();

        return false;
    }

    /**
     * Baca izin penghapusan dari environment.
     *
     * env() tidak bisa dipakai begitu saja karena mengembalikan null setelah
     * config di-cache, jadi getenv() dipakai sebagai cadangan.
     */
    private function izinDestructive(): bool
    {
        $nilai = env(self::ENVIZIN);

        if ($nilai === null) {
            $nilai = getenv(self::ENVIZIN) ?: null;
        }

        return filter_var($nilai, FILTER_VALIDATE_BOOLEAN);
    }
}