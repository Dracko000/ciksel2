<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Mendaftarkan nomor seri mesin absensi ZKTeco ke tabel devices.
 *
 * Sengaja tidak dipanggil dari DatabaseSeeder supaya `php artisan db:seed`
 * tidak ikut menyentuh data mesin. Jalankan sendiri bila dibutuhkan:
 *
 *     php artisan db:seed --class=DeviceSeeder
 *
 * Idempoten: memakai updateOrInsert pada no_sn, jadi menjalankan berkali-kali
 * tidak menghasilkan baris duplikat.
 *
 * secret_token sengaja dibiarkan NULL. Middleware AuthorizeIclockDevice hanya
 * mewajibkan token bila kolom itu terisi, sehingga mesin bisa langsung mengirim
 * log begitu SN-nya terdaftar. Kalau nanti token diisi lewat halaman "Mesin
 * Absensi", token yang sama wajib dikonfigurasi di mesin, jika tidak mesin akan
 * mendapat 403 ERROR: INVALID DEVICE TOKEN.
 *
 * nama dan lokasi diambil dari .env agar tidak perlu menyentuh kode.
 *
 *     DEVICE_1_NAMA / DEVICE_1_LOKASI
 *     DEVICE_2_NAMA / DEVICE_2_LOKASI
 */
class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            [
                'no_sn' => 'BWS5255100008',
                'nama' => env('DEVICE_1_NAMA', 'Mesin Absensi 1'),
                'lokasi' => env('DEVICE_1_LOKASI', 'Belum ditentukan'),
            ],
            [
                'no_sn' => 'GED7260600379',
                'nama' => env('DEVICE_2_NAMA', 'Mesin Absensi 2'),
                'lokasi' => env('DEVICE_2_LOKASI', 'Belum ditentukan'),
            ],
        ];

        foreach ($devices as $device) {
            DB::table('devices')->updateOrInsert(
                ['no_sn' => $device['no_sn']],
                [
                    'nama' => $device['nama'],
                    'lokasi' => $device['lokasi'],
                    'secret_token' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}