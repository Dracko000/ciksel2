<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Notifications\DeviceOnline;
use App\Support\Notifikasi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menghubungkan setiap kontak device ZKTeco dengan device yang sudah
 * terdaftar di tabel devices. Handshake versi sebelumnya mendaftarkan SN
 * apa pun yang datang, sehingga siapa pun bisa menyamar sebagai device dan
 * mengirim log absensi palsu. SN yang tidak dikenal kini ditolak, dan device
 * yang punya secret_token wajib menyajikannya.
 */
class AuthorizeIclockDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $sn = $request->input('SN') ?? $request->header('X-Device-SN');

        if (!is_string($sn) || trim($sn) === '') {
            return $this->deny('ERROR: SN REQUIRED');
        }

        $sn = trim($sn);

        $device = Device::where('no_sn', $sn)->first();

        if (!$device) {
            return $this->deny('ERROR: DEVICE NOT REGISTERED');
        }

        $expected = $device->secret_token;

        if (is_string($expected) && $expected !== '') {
            $provided = $request->header('X-Device-Token')
                ?? $request->input('secret_token');

            if (!is_string($provided) || !hash_equals($expected, $provided)) {
                return $this->deny('ERROR: INVALID DEVICE TOKEN');
            }
        }

        $sebelumnyaOnline = $device->online !== null;

        $device->forceFill([
            'online' => now(),
            'last_seen_at' => now(),
            'ip_address' => $request->ip(),
        ])->save();

        // Hanya beri tahu admin saat mesin benar-benar kembali online, bukan
        // tiap request. Mesin absensi menghubungi server tiap beberapa detik
        // sehingga notifikasi akan flooding bila dikirim setiap kontak.
        if (! $sebelumnyaOnline) {
            Notifikasi::keAdmin(new DeviceOnline($device));
        }

        $request->attributes->set('iclock_device', $device);

        return $next($request);
    }

    private function deny(string $message): Response
    {
        return response($message, 403)->header('Content-Type', 'text/plain');
    }
}