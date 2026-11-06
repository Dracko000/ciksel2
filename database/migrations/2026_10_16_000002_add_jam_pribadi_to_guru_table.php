<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jam masuk/pulang guru diatur per orang.
 *
 * Sebelumnya jam guru disimpan sebagai satu baris global di tabel
 * `pengaturan_jam` (kategori 'guru'), sehingga seluruh guru & tendik memakai
 * acuan yang sama. Sekarang setiap guru punya jam pribadinya sendiri di kolom
 * `jam_masuk` / `jam_pulang` pada tabel `guru`; guru yang belum diatur tidak
 * dinilai terlambat.
 *
 * Baris global lama sengaja dihapus karena tidak lagi dipakai. Tabel
 * `pengaturan_jam` tetap ada untuk jam per tingkat kelas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            $table->time('jam_masuk')->nullable()->after('is_tendik');
            $table->time('jam_pulang')->nullable()->after('jam_masuk');
        });

        // Hapus acuan jam guru global yang sudah digantikan jam per orang.
        DB::table('pengaturan_jam')->where('kategori', 'guru')->delete();
    }

    public function down(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            $table->dropColumn(['jam_masuk', 'jam_pulang']);
        });
    }
};
