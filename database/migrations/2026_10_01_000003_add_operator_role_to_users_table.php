<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users.role ada di database adms tetapi tidak pernah dibuat oleh migration
 * 2014_10_12_000000_create_users_table, sehingga instalasi baru tidak punya
 * kolom ini sama sekali. Kolom dibuat di sini bila belum ada, lalu ditambah
 * nilai operator.
 *
 * Nilai guru, ortu dan siswa dipertahankan agar akun lama tidak ikut berubah.
 * down() hanya mengembalikan nilai enum, tidak menghapus kolom atau data.
 */
return new class extends Migration
{
    private const WITH_OPERATOR = "'admin','operator','guru','ortu','siswa'";

    private const ORIGINAL = "'admin','guru','ortu','siswa'";

    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (! Schema::hasColumn('users', 'role')) {
            DB::statement(
                'ALTER TABLE `users` ADD `role` ENUM('.self::WITH_OPERATOR.") NOT NULL DEFAULT 'siswa' AFTER `password`"
            );

            return;
        }

        DB::statement(
            'ALTER TABLE `users` MODIFY `role` ENUM('.self::WITH_OPERATOR.") NOT NULL DEFAULT 'siswa'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE `users` SET `role` = 'siswa' WHERE `role` = 'operator'");

        DB::statement(
            'ALTER TABLE `users` MODIFY `role` ENUM('.self::ORIGINAL.") NOT NULL DEFAULT 'siswa'"
        );
    }
};
