<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan jam masuk/pulang.
 *
 * kategori = 'kelas' -> satu baris per tingkat (1..6). Dipakai JamKelas untuk
 *   menilai keterlambatan sekaligus menebak jenis tap masuk/pulang.
 * kategori = 'guru'  -> satu baris (tingkat null). Jam acuan guru/tendik yang
 *   tidak menjadi wali kelas.
 *
 * Bila belum ada baris, sistem memakai jadwal bawaan di JamKelas::JADWAL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_jam', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 20);
            $table->unsignedTinyInteger('tingkat')->nullable();
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->timestamps();

            $table->unique(['kategori', 'tingkat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_jam');
    }
};
