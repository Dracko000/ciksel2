<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nip')->unique()->nullable();
            $table->string('nama');
            $table->string('rfid_kartu')->nullable();
            $table->string('wajah_id_zkteco')->nullable(); // Maps to employee_id in ZKTeco
            $table->boolean('is_tendik')->default(false); // Tenaga Pendidik / Admin Sekolah
            $table->timestamps();
        });

        // Add foreign key for wali_kelas_id now that guru table exists
        Schema::table('kelas', function (Blueprint $table) {
            $table->foreign('wali_kelas_id')->references('id')->on('guru')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['wali_kelas_id']);
        });
        Schema::dropIfExists('guru');
    }
};
