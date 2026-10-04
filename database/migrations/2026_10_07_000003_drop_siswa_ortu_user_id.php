<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sekolah ini tidak punya akun orang tua terpisah: orang tua memakai akun
 * anaknya. Kolom ortu_user_id tidak pernah terisi (0 baris) dan relasi
 * Siswa::ortu() sudah tidak ada yang memakainya, jadi keduanya dibuang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('siswa', 'ortu_user_id')) {
            return;
        }

        $terisi = DB::table('siswa')->whereNotNull('ortu_user_id')->count();

        if ($terisi > 0) {
            throw new RuntimeException(
                "Ada {$terisi} baris siswa dengan ortu_user_id terisi. "
                . 'Migrasi dibatalkan supaya relasi orang tua tidak hilang diam-diam.'
            );
        }

        $fk = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            ['siswa', 'ortu_user_id']
        );

        Schema::table('siswa', function ($table) use ($fk) {
            // Nama constraint harus diambil dari DB. dropForeign('ortu_user_id')
            // menghasilkan "siswa_ortu_user_id", sedangkan nama sebenarnya
            // "siswa_ortu_user_id_foreign", jadi tidak akan cocok.
            if ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            }

            $table->dropColumn('ortu_user_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('siswa', 'ortu_user_id')) {
            return;
        }

        Schema::table('siswa', function ($table) {
            $table->unsignedBigInteger('ortu_user_id')->nullable()->after('user_id');
            $table->foreign('ortu_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};