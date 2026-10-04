<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom pin menjadi kunci lookup device yang tunggal. Absensi dari device
 * hanya membawa satu employee_id, sedangkan identitas device sebelumnya
 * tersebar pada rfid_kartu dan wajah_id_zkteco sehingga pencocokan tidak
 * punya urutan yang tegas. Kolom lama tidak dihapus karena masih menyimpan
 * kode yang terdaftar di perangkat.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = ['siswa', 'guru'];

    /**
     * Urutan prioritas saat mengisi pin. rfid_kartu didahulukan karena kode
     * rfid lebih lazim dipakai device ZKTeco untuk absensi harian.
     *
     * @var array<int, string>
     */
    private const SOURCES = ['rfid_kartu', 'wajah_id_zkteco'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function ($table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'pin')) {
                    $table->string('pin', 32)->nullable()->after('id');
                }
            });

            $this->backfillPin($tableName);
            $this->dropLegacyIdentifierIndexes($tableName);

            Schema::table($tableName, function ($table) use ($tableName) {
                if (! $this->indexExists($tableName, "{$tableName}_pin_index")) {
                    $table->index('pin', "{$tableName}_pin_index");
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'pin')) {
                continue;
            }

            Schema::table($tableName, function ($table) use ($tableName) {
                if ($this->indexExists($tableName, "{$tableName}_pin_index")) {
                    $table->dropIndex("{$tableName}_pin_index");
                }
            });

            Schema::table($tableName, function ($table) {
                $table->dropColumn('pin');
            });

            foreach (self::SOURCES as $column) {
                $indexName = "{$tableName}_{$column}_index";

                if (Schema::hasColumn($tableName, $column) && ! $this->indexExists($tableName, $indexName)) {
                    Schema::table($tableName, function ($table) use ($column, $indexName) {
                        $table->index($column, $indexName);
                    });
                }
            }
        }
    }

    /**
     * Isi pin dari identitas device yang sudah ada. Baris yang sudah punya pin
     * tidak disentuh sehingga migration ini aman dijalankan berulang.
     */
    private function backfillPin(string $tableName): void
    {
        $sources = array_values(array_filter(
            self::SOURCES,
            fn (string $column) => Schema::hasColumn($tableName, $column)
        ));

        if ($sources === []) {
            return;
        }

        Schema::getConnection()
            ->table($tableName)
            ->whereNull('pin')
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($tableName, $sources) {
                foreach ($rows as $row) {
                    $pin = null;

                    foreach ($sources as $column) {
                        $value = $row->{$column} ?? null;

                        if ($value !== null && $value !== '') {
                            $pin = (string) $value;
                            break;
                        }
                    }

                    if ($pin === null) {
                        continue;
                    }

                    Schema::getConnection()
                        ->table($tableName)
                        ->where('id', $row->id)
                        ->update(['pin' => $pin]);
                }
            });
    }

    private function dropLegacyIdentifierIndexes(string $tableName): void
    {
        foreach (self::SOURCES as $column) {
            $indexName = "{$tableName}_{$column}_index";

            if (Schema::hasColumn($tableName, $column) && $this->indexExists($tableName, $indexName)) {
                Schema::table($tableName, function ($table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
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
