<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menguatkan tabel pivot ekstrakulikuler_siswa.
 *
 * Dua masalah yang ditemukan:
 *  1. ekstrakulikuler_id tidak punya foreign key, jadi menghapus ekstrakulikuler
 *     meninggalkan baris pivot yatim.
 *  2. Unique key lama melibatkan tahun_ajaran dan semester yang nullable. MySQL
 *     menganggap setiap NULL berbeda, sehingga siswa yang sama bisa terdaftar
 *     lebih dari sekali pada kegiatan yang sama.
 */
return new class extends Migration
{
    private const TABEL = 'ekstrakulikuler_siswa';

    private const UNIQUE_LAMA = 'ekstrakulikuler_siswa_unik';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABEL)) {
            return;
        }

        // Buang baris yatim sebelum menambah foreign key.
        DB::table(self::TABEL)->whereNotIn('ekstrakulikuler_id', DB::table('ekstrakulikuler')->select('id'))->delete();
        DB::table(self::TABEL)->whereNotIn('siswa_id', DB::table('siswa')->select('id'))->delete();

        // Pastikan tidak ada siswa yang terdaftar ganda lebih dari sekali.
        $duplikat = DB::table(self::TABEL)
            ->select('ekstrakulikuler_id', 'siswa_id')
            ->groupBy('ekstrakulikuler_id', 'siswa_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplikat as $baris) {
            DB::table(self::TABEL)
                ->where('ekstrakulikuler_id', $baris->ekstrakulikuler_id)
                ->where('siswa_id', $baris->siswa_id)
                ->orderBy('id')
                ->skip(1)
                ->take($this->jumlahDuplikat($baris))
                ->delete();
        }

        Schema::table(self::TABEL, function (Blueprint $table) {
            if ($this->punyaIndex(self::TABEL, self::UNIQUE_LAMA)) {
                $table->dropUnique(self::UNIQUE_LAMA);
            }
        });

        Schema::table(self::TABEL, function (Blueprint $table) {
            $table->unique(['ekstrakulikuler_id', 'siswa_id'], 'ekstrakulikuler_siswa_unik');
        });

        Schema::table(self::TABEL, function (Blueprint $table) {
            if (! $this->punyaForeignKey(self::TABEL, 'ekstrakulikuler_id')) {
                $table->foreign('ekstrakulikuler_id')
                    ->references('id')->on('ekstrakulikuler')->onDelete('cascade');
            }

            if (! $this->punyaForeignKey(self::TABEL, 'siswa_id')) {
                $table->foreign('siswa_id')
                    ->references('id')->on('siswa')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABEL)) {
            return;
        }

        Schema::table(self::TABEL, function (Blueprint $table) {
            if ($this->punyaForeignKey(self::TABEL, 'siswa_id')) {
                $table->dropForeign(['siswa_id']);
            }

            if ($this->punyaForeignKey(self::TABEL, 'ekstrakulikuler_id')) {
                $table->dropForeign(['ekstrakulikuler_id']);
            }

            if ($this->punyaIndex(self::TABEL, 'ekstrakulikuler_siswa_unik')) {
                $table->dropUnique('ekstrakulikuler_siswa_unik');
            }

            $table->unique(
                ['ekstrakulikuler_id', 'siswa_id', 'tahun_ajaran', 'semester'],
                self::UNIQUE_LAMA
            );
        });
    }

    private function jumlahDuplikat(object $baris): int
    {
        return DB::table(self::TABEL)
            ->where('ekstrakulikuler_id', $baris->ekstrakulikuler_id)
            ->where('siswa_id', $baris->siswa_id)
            ->count() - 1;
    }

    private function punyaIndex(string $table, string $index): bool
    {
        $ada = DB::selectOne(
            'SELECT COUNT(*) AS jumlah FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return ((int) ($ada->jumlah ?? 0)) > 0;
    }

    private function punyaForeignKey(string $table, string $column): bool
    {
        $ada = DB::selectOne(
            'SELECT COUNT(*) AS jumlah FROM information_schema.key_column_usage
             WHERE table_schema = DATABASE() AND table_name = ?
               AND column_name = ? AND referenced_table_name IS NOT NULL',
            [$table, $column]
        );

        return ((int) ($ada->jumlah ?? 0)) > 0;
    }
};