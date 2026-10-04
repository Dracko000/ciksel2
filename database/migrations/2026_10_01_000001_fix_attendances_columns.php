<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * attendances.employee_id diubah dari integer ke varchar karena PIN ZKTeco
 * dapat berupa alfanumerik. Kolom status1..5 diubah dari tinyint(1) ke
 * tinyint unsigned karena kolom ke-3 dan ke-4 pada baris ATTLOG menyimpan
 * kode 0-5, bukan boolean.
 *
 * Nilai lama 2-5 sudah rusak menjadi 1 dan tidak dapat dikembalikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `attendances` MODIFY `employee_id` VARCHAR(32) NOT NULL');

            foreach (['status1', 'status2', 'status3', 'status4', 'status5'] as $column) {
                DB::statement("ALTER TABLE `attendances` MODIFY `{$column}` TINYINT UNSIGNED NULL");
            }
        }

        Schema::table('attendances', function ($table) {
            $table->index('timestamp', 'attendances_timestamp_index');
            $table->index(['sn', 'timestamp'], 'attendances_sn_timestamp_index');
            $table->index('employee_id', 'attendances_employee_id_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function ($table) {
            $table->dropIndex('attendances_timestamp_index');
            $table->dropIndex('attendances_sn_timestamp_index');
            $table->dropIndex('attendances_employee_id_index');
        });

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `attendances` MODIFY `employee_id` INT NOT NULL');

        foreach (['status1', 'status2', 'status3', 'status4', 'status5'] as $column) {
            DB::statement("ALTER TABLE `attendances` MODIFY `{$column}` TINYINT(1) NULL");
        }
    }
};
