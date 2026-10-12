<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Guru;
use App\Models\Siswa;
use App\Notifications\AbsenTercatat;
use App\Support\Absensi\JamKelas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class AttendanceObserver
{
    public function creating(Attendance $attendance): void
    {
        if (empty($attendance->jenis)) {
            $attendance->jenis = $this->tebakJenis($attendance);
        }
    }

    public function created(Attendance $attendance): void
    {
        try {
            $waktu = $attendance->timestamp instanceof Carbon
                ? $attendance->timestamp->format('H:i')
                : Carbon::parse($attendance->timestamp)->format('H:i');

            $siswa = Siswa::where('wajah_id_zkteco', $attendance->employee_id)
                ->orWhere('pin', $attendance->employee_id)
                ->orWhere('rfid_kartu', $attendance->employee_id)
                ->first();

            if ($siswa && $siswa->user) {
                Notification::send($siswa->user, new AbsenTercatat($attendance->jenis, $waktu, $siswa->nama ?? $siswa->user->name));

                return;
            }

            $guru = Guru::where('wajah_id_zkteco', $attendance->employee_id)
                ->orWhere('pin', $attendance->employee_id)
                ->orWhere('rfid_kartu', $attendance->employee_id)
                ->first();

            if ($guru && $guru->user) {
                Notification::send($guru->user, new AbsenTercatat($attendance->jenis, $waktu, $guru->nama ?? $guru->user->name));
            }
        } catch (\Exception $e) {
            // ignore notification errors
        }
    }

    /**
     * Tebak apakah tap ini "masuk" atau "pulang".
     *
     * Mesin ZKTeco hanya mengirim waktu mentah tanpa penanda. Aturan lama
     * "jam >= 12 berarti pulang" rusak untuk kelas siang (3 & 4) yang justru
     * masuk pukul 12:30: tap masuk mereka tercatat sebagai pulang. Karena itu
     * pembatasnya kini titik tengah rentang belajar kelas orang tersebut.
     * Guru/tendik tanpa kelas memakai patokan tengah hari seperti sebelumnya.
     */
    private function tebakJenis(Attendance $attendance): string
    {
        $waktu = $this->waktu($attendance);

        if ($waktu === null) {
            return 'masuk';
        }

        $titikTengah = JamKelas::titikTengah($this->kelasOrang($attendance->employee_id));

        if ($titikTengah !== null) {
            return $waktu->format('H:i') < $titikTengah ? 'masuk' : 'pulang';
        }

        return $waktu->hour >= 12 ? 'pulang' : 'masuk';
    }

    private function waktu(Attendance $attendance): ?Carbon
    {
        $timestamp = $attendance->timestamp;

        if ($timestamp instanceof Carbon) {
            return $timestamp;
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Nama kelas orang yang tap: kelas siswa, atau kelas yang diampu wali
     * kelas bila yang tap adalah guru. Null bila tidak dapat ditentukan
     * (guru/tendik biasa).
     */
    private function kelasOrang(?string $employeeId): ?string
    {
        if ($employeeId === null || $employeeId === '') {
            return null;
        }

        $siswa = Siswa::with('kelas')
            ->where('wajah_id_zkteco', $employeeId)
            ->orWhere('pin', $employeeId)
            ->orWhere('rfid_kartu', $employeeId)
            ->first();

        if ($siswa) {
            return $siswa->kelas?->nama_kelas;
        }

        $guru = Guru::with('kelasWali')
            ->where('wajah_id_zkteco', $employeeId)
            ->orWhere('pin', $employeeId)
            ->orWhere('rfid_kartu', $employeeId)
            ->first();

        return $guru?->kelasWali->first()?->nama_kelas;
    }
}
