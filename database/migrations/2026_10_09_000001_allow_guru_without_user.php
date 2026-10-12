<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Beberapa PTK dari dokumen pembagian tugas belum memiliki akun login
 * (mis. guru honorer tanpa NIP). Baris guru tetap perlu disimpan agar bisa
 * dijadikan wali kelas / pembina tanpa harus membuat akun terlebih dahulu,
 * sehingga kolom guru.user_id dibuat nullable.
 *
 * Relasi ke users tetap dipertahankan (ON DELETE CASCADE) supaya guru yang
 * akunnya dihapus ikut terhapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        // doctrine/dbal tidak terpasang, jadi ubah kolom lewat SQL native.
        // MySQL mengizinkan perubahan nullability selama tipe kolom tetap
        // cocok dengan kolom yang direferensikan, tanpa menjatuhkan foreign key.
        DB::statement('ALTER TABLE guru MODIFY user_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE guru MODIFY user_id BIGINT UNSIGNED NOT NULL');
    }
};
