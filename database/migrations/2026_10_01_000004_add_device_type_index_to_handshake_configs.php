<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Baris device_type dibaca pada setiap handshake, jadi perlu indeks.
 * Sengaja tidak unik agar aman pada instalasi yang sudah punya baris ganda
 * untuk satu tipe device.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_handshake_configs')) {
            return;
        }

        Schema::table('device_handshake_configs', function ($table) {
            $table->index('device_type', 'device_handshake_configs_device_type_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('device_handshake_configs')) {
            return;
        }

        Schema::table('device_handshake_configs', function ($table) {
            $table->dropIndex('device_handshake_configs_device_type_index');
        });
    }
};
