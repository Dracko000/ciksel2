<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan identitas device disepakati sebagai PIN = NIS untuk siswa dan
 * PIN = NIP untuk guru. Migrasi 2026_10_01_000006 mengisi pin dari
 * rfid_kartu/wajah_id_zkteco, tetapi kedua kolom itu kosong pada data yang
 * ada sehingga pin tertinggal NULL dan device tidak akan pernah cocok.
 * Migrasi ini menjadikan NIS/NIP sebagai sumber tunggal yang authoritative.
 */
return new class extends Migration
{
    /** @var array<int, array<int, string>> */
    private const SOURCES = [
        'siswa' => ['pin', 'nis'],
        'guru' => ['pin', 'nip'],
    ];

    public function up(): void
    {
        foreach (self::SOURCES as $tableName => [$pinColumn, $idColumn]) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, $pinColumn) || ! Schema::hasColumn($tableName, $idColumn)) {
                continue;
            }

            DB::table($tableName)
                ->whereNotNull($idColumn)
                ->where($idColumn, '!=', '')
                ->update([$pinColumn => DB::raw("TRIM($idColumn)")]);
        }
    }

    public function down(): void
    {
        foreach (self::SOURCES as $tableName => [$pinColumn]) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $pinColumn)) {
                DB::table($tableName)->update([$pinColumn => null]);
            }
        }
    }
};