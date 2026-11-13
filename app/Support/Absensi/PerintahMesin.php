<?php

namespace App\Support\Absensi;

use App\Models\Device;
use App\Models\DeviceCommand;
use InvalidArgumentException;

/**
 * Pembungkus perintah ZKTeco push-protocol.
 *
 * Mesin absensi mengambil perintah lewat `GET /iclock/getrequest`, lalu
 * melaporkan hasilnya ke `POST /iclock/devicecmd`. Semua perintah di sini
 * bersifat destruktif di sisi mesin: data yang dihapus tidak bisa
 * dikembalikan lewat aplikasi ini.
 */
class PerintahMesin
{
    /**
     * Simpan perintah ke antrean mesin.
     *
     * Perintah tidak langsung dikirim; mesin akan mengambilnya sendiri pada
     * polling berikutnya (biasanya beberapa detik sampai satu menit).
     */
    public static function antre(Device $device, string $command): DeviceCommand
    {
        $command = trim($command);

        if ($command === '') {
            throw new InvalidArgumentException('Perintah tidak boleh kosong.');
        }

        // Protokol memisahkan perintah dengan baris baru, jadi karakter itu
        // tidak boleh ikut masuk ke isi perintah.
        if (preg_match('/[\r\n]/', $command)) {
            throw new InvalidArgumentException('Perintah tidak boleh mengandung baris baru.');
        }

        return DeviceCommand::create([
            'device_id' => $device->id,
            'no_sn' => $device->no_sn,
            'command' => $command,
            'status' => 'pending',
        ]);
    }

    /**
     * Hapus satu user (PIN) dari mesin.
     *
     * PIN adalah `employee_id` yang dipakai mesin, biasanya sama dengan
     * `wajah_id_zkteco` pada data siswa/guru.
     */
    public static function hapusUser(Device $device, string $pin): DeviceCommand
    {
        $pin = trim($pin);

        if ($pin === '' || ! preg_match('/^[A-Za-z0-9_.\-]+$/', $pin)) {
            throw new InvalidArgumentException('PIN hanya boleh berisi huruf, angka, titik, garis bawah, atau strip.');
        }

        return self::antre($device, 'DATA DELETE USERINFO PIN='.$pin);
    }

    /**
     * Hapus seluruh user di mesin. Sidik jari, wajah, dan kartu ikut hilang.
     */
    public static function hapusSemuaUser(Device $device): DeviceCommand
    {
        return self::antre($device, 'DATA DELETE USERINFO');
    }

    /**
     * Bersihkan log absensi di mesin.
     */
    public static function hapusLog(Device $device): DeviceCommand
    {
        return self::antre($device, 'CLEAR LOG');
    }

    /**
     * Minta mesin restart.
     */
    public static function reboot(Device $device): DeviceCommand
    {
        return self::antre($device, 'REBOOT');
    }
}
