<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Orang tua tidak lagi memakai akun sendiri, melainkan masuk memakai akun
     * siswa. Karena itu role ortu dihapus dari daftar role.
     *
     * Akun yang sudah ada tidak dihapus supaya nama dan email nya tetap bisa
     * dipakai sebagai data kontak wali, dan siswa.ortu_user_id tetap menunjuk
     * ke sana. Role-nya dikosongkan karena kolom enum tidak mungkin lagi
     * menerima nilai ortu, dan role kosong membuat akun tersebut otomatis
     * kehilangan akses portal.
     */
    public function up(): void
    {
        // Kolom harus dibuat nullable lebih dulu, baru isinya dikosongkan.
        // Urutan terbalik akan ditolak MySQL karena role masih wajib diisi.
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','operator','guru','ortu','siswa') NULL");

        DB::table('users')->where('role', 'ortu')->update(['role' => null]);

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','operator','guru','siswa') NULL");
    }

    /**
     * Role asli akun kontak tidak bisa dipulihkan karena nilai aslinya sudah
     * hilang bersama penghapusan nilai ortu dari enum. Akun tanpa role dikembalikan
     * sebagai operator supaya kolom boleh dibuat wajib lagi, dan operator adalah
     * role staff yang paling dekat dengan maksud akun kontak sekolah.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','operator','guru','siswa') NULL");

        DB::table('users')->whereNull('role')->update(['role' => 'operator']);

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','operator','guru','ortu','siswa') NOT NULL");
    }
};
