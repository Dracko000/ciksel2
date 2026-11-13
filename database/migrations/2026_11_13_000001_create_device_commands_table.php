<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Antrean perintah untuk mesin ZKTeco.
 *
 * Mesin absensi menghubungi server sendiri lewat `GET /iclock/getrequest`.
 * Selama ini server hanya menjawab "OK", sehingga tidak ada cara menghapus
 * user atau log dari sisi aplikasi. Tabel ini menyimpan perintah yang
 * menunggu diambil mesin, lalu hasilnya dicatat saat mesin melapor balik
 * lewat `POST /iclock/devicecmd`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('no_sn');
            $table->text('command');
            $table->string('status', 20)->default('pending'); // pending|sent|done|failed
            $table->string('return_code', 50)->nullable();
            $table->text('result')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['no_sn', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
