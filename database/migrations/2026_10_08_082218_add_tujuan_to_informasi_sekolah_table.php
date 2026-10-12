<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('informasi_sekolah', function (Blueprint $table) {
            $table->enum('tujuan', ['umum', 'kelas'])->default('umum')->after('tanggal_publikasi');
            $table->unsignedBigInteger('kelas_id')->nullable()->after('tujuan');
            $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('informasi_sekolah', function (Blueprint $table) {
            $table->dropForeign(['kelas_id']);
            $table->dropColumn(['tujuan', 'kelas_id']);
        });
    }
};
