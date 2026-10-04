<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus role "operator" dari enum users.role.
 *
 * Role itu tidak pernah dipakai akun mana pun dan tidak punya halaman sendiri.
 * Modul portal orang tua yang selama ini dituju role "ortu" tidak dapat
 * dibuka karena nilai tersebut tidak ada di enum; form pengajuan izin sudah
 * dipindahkan ke sisi admin, jadi tidak ada lagi fitur yang bergantung pada
 * role yang tidak ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','guru','siswa') NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','operator','guru','siswa') NOT NULL");
    }
};