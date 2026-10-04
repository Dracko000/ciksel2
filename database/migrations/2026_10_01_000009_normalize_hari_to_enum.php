<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * hari sebelumnya varchar bebas sehingga bisa berisi "senin", "Senin ",
 * atau salah ketik. Nilai tersebut dipakai untuk menentukan jam mulai pada
 * perhitungan keterlambatan, sehingga daftar hari harus tertutup. Nilai yang
 * tidak dikenal dipetakan ke Senin agar baris jadwal tidak hilang.
 */
return new class extends Migration
{
    private const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /** @var array<int, string> */
    private const TABLES = ['jadwal_pelajaran', 'ekstrakulikuler'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'hari')) {
                continue;
            }

            $this->normaliseValues($tableName);
            $this->toEnum($tableName);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'hari')) {
                continue;
            }

            DB::statement(
                'ALTER TABLE `'.$tableName.'` MODIFY `hari` varchar(255) NULL'
            );
        }
    }

    /**
     * Samakan kapitalisasi dan spasi sebelum kolom dipersempit menjadi enum.
     * Nilai tak dikenal dipetakan ke Senin agar tidak ada baris hilang.
     */
    private function normaliseValues(string $tableName): void
    {
        $target = self::DAYS[0];

        foreach (self::DAYS as $day) {
            DB::table($tableName)
                ->whereRaw('LOWER(TRIM(`hari`)) = ?', [mb_strtolower($day)])
                ->update(['hari' => $day]);
        }

        DB::table($tableName)
            ->whereNotNull('hari')
            ->whereNotIn('hari', self::DAYS)
            ->update(['hari' => $target]);
    }

    private function toEnum(string $tableName): void
    {
        $definition = 'enum('.implode(',', array_map(
            fn (string $day) => "'".$day."'",
            self::DAYS
        )).')';

        DB::statement('ALTER TABLE `'.$tableName.'` MODIFY `hari` '.$definition.' NULL');
    }
};
