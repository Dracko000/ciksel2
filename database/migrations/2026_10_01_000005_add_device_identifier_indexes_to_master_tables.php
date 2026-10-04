<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom identifier device pada siswa dan guru belum berindex, sehingga setiap
 * tap device memaksa full table scan untuk mencari pemilik PIN. Index di sini
 * sengaja tidak UNIQUE karena rfid_kartu boleh NULL dan kartu rfid dapat
 * dipakai lebih dari satu orang, sedangkan wajah_id_zkteco uniqueness
 * ditangani sebagai aturan aplikasi.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $identifierColumns = [
        'siswa' => ['rfid_kartu', 'wajah_id_zkteco'],
        'guru' => ['rfid_kartu', 'wajah_id_zkteco'],
    ];

    public function up(): void
    {
        foreach ($this->identifierColumns as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function ($table) use ($tableName, $columns) {
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($tableName, $column)) {
                        continue;
                    }

                    $indexName = "{$tableName}_{$column}_index";

                    if (! $this->indexExists($tableName, $indexName)) {
                        $table->index($column, $indexName);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->identifierColumns as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function ($table) use ($tableName, $columns) {
                foreach ($columns as $column) {
                    $indexName = "{$tableName}_{$column}_index";

                    if (Schema::hasColumn($tableName, $column) && $this->indexExists($tableName, $indexName)) {
                        $table->dropIndex($indexName);
                    }
                }
            });
        }
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        foreach (Schema::getIndexes($tableName) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
