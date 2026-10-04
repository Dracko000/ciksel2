<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AdmsNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

/**
 * Helper kecil untuk mengirim notifikasi in-app ke pihak yang tepat.
 */
class Notifikasi
{
    /**
     * Kirim ke seluruh akun admin.
     */
    public static function keAdmin(AdmsNotification $notifikasi): int
    {
        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            return 0;
        }

        Notification::send($admins, $notifikasi);

        return $admins->count();
    }

    /**
     * Kirim ke akun siswa pemilik data tersebut.
     *
     * Sekolah ini tidak punya akun orang tua terpisah, jadi akun siswa
     * sekaligus dipakai orang tuanya untuk membuka notifikasi ini.
     */
    public static function kePemilikSiswa(int $siswaId, AdmsNotification $notifikasi): int
    {
        $user = \App\Models\Siswa::find($siswaId)?->user;

        if (! $user) {
            return 0;
        }

        $user->notify($notifikasi);

        return 1;
    }

    /**
     * Kirim ke seluruh siswa peserta suatu ekstrakulikuler.
     */
    public static function kePeserta(Model $ekstrakulikuler, AdmsNotification $notifikasi): int
    {
        $users = $ekstrakulikuler->siswa()->with('user')->get()->pluck('user')->filter();

        if ($users->isEmpty()) {
            return 0;
        }

        Notification::send($users, $notifikasi);

        return $users->count();
    }
}