<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * last_seen_at menyimpan waktu kontak terakhir device secara permanen,
 * sedangkan online dipakai sebagai status dan dinolkan oleh command
 * adms:devices-offline bila sudah melewati ADMS_DEVICE_OFFLINE_AFTER.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        Schema::table('devices', function ($table) {
            if (! Schema::hasColumn('devices', 'secret_token')) {
                $table->string('secret_token', 64)->nullable()->after('lokasi');
            }

            if (! Schema::hasColumn('devices', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('secret_token');
            }

            if (! Schema::hasColumn('devices', 'last_seen_at')) {
                $table->dateTime('last_seen_at')->nullable()->after('online');
            }
        });

        Schema::table('devices', function ($table) {
            $table->index('last_seen_at', 'devices_last_seen_at_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        Schema::table('devices', function ($table) {
            $table->dropIndex('devices_last_seen_at_index');
        });

        Schema::table('devices', function ($table) {
            foreach (['last_seen_at', 'ip_address', 'secret_token'] as $column) {
                if (Schema::hasColumn('devices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
