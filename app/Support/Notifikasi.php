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
     * Kirim ke user milik seorang siswa (siswa itu sendiri dan orang tuanya
     * bila sudah terhubung).
     */
    public static function keSiswaDanOrtu(int $siswaId, AdmsNotification $notifikasi): int
    {
        $siswa = \App\Models\Siswa::with(['user', 'ortu'])->find($siswaId);

        if (! $siswa) {
            return 0;
        }

        $tujuan = array_values(array_filter([$siswa->user, $siswa->ortu]));

        if ($tujuan === []) {
            return 0;
        }

        Notification::send($tujuan, $notifikasi);

        return count($tujuan);
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