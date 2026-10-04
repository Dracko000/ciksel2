<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Ekstrakulikuler menggantikan jadwal kegiatan yang pernah ada di portal.
 * Jadwal disimpan langsung pada tabel utama karena satu kegiatan biasanya
 * berlangsung pada satu hari dalam seminggu. Bila satu kegiatan butuh
 * beberapa hari, cukup tambah baris dengan nama kegiatan yang sama.
 *
 * Kehadiran siswa pada kegiatan dicatat lewat enrollment pada tabel
 * ekstrakulikuler_siswa. Absensi kegiatan tetap memakai tabel attendances
 * yang sama dengan absensi harian, karena device tidak membedakan jenis
 * absensi pada protokol ATTLOG.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ekstrakulikuler')) {
            Schema::create('ekstrakulikuler', function ($table) {
                $table->id();
                $table->string('nama');
                $table->text('deskripsi')->nullable();
                $table->foreignId('guru_id')->nullable();
                $table->string('hari')->nullable();
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->string('lokasi')->nullable();
                $table->string('tahun_ajaran', 20)->nullable();
                $table->string('semester', 20)->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();

                $table->index('guru_id', 'ekstrakulikuler_guru_id_foreign');
                $table->index(['hari', 'jam_mulai'], 'ekstrakulikuler_jadwal_index');
            });
        }

        if (! Schema::hasTable('ekstrakulikuler_siswa')) {
            Schema::create('ekstrakulikuler_siswa', function ($table) {
                $table->id();
                $table->foreignId('ekstrakulikuler_id');
                $table->foreignId('siswa_id');
                $table->string('tahun_ajaran', 20)->nullable();
                $table->string('semester', 20)->nullable();
                $table->timestamps();

                $table->unique(
                    ['ekstrakulikuler_id', 'siswa_id', 'tahun_ajaran', 'semester'],
                    'ekstrakulikuler_siswa_unik'
                );
                $table->index('siswa_id', 'ekstrakulikuler_siswa_siswa_id_foreign');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ekstrakulikuler_siswa');
        Schema::dropIfExists('ekstrakulikuler');
    }
};
