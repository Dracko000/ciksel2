<?php

namespace App\Support;

/**
 * Tahun ajaran default aplikasi.
 *
 * Nilai tahun ajaran dipakai di beberapa form (input nilai, ekstrakulikuler),
 * jadi defaultnya didefinisikan di satu tempat saja supaya tidak ada form yang
 * diam-diam menyimpan data ke tahun ajaran lawas.
 */
class TahunAjaran
{
    /** Tahun ajaran berjalan. */
    public const DEFAULT = '2026/2027';

    /**
     * Daftar pilihan tahun ajaran untuk select, berpusat pada tahun ajaran
     * tertentu (default: tahun ajaran berjalan).
     *
     * @return array<int, string>
     */
    public static function daftar(string $acuan = self::DEFAULT, int $sebelum = 2, int $sesudah = 1): array
    {
        $mulai = (int) explode('/', $acuan)[0];

        return collect(range($mulai - $sebelum, $mulai + $sesudah))
            ->map(fn ($tahun) => $tahun.'/'.($tahun + 1))
            ->values()
            ->all();
    }
}
