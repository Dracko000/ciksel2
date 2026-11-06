<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Guru;
use App\Models\Siswa;
use App\Notifications\AbsenTercatat;
use App\Support\Absensi\JamGuru;
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
     * pembatasnya kini titik tengah rentang jam orang tersebut: jam kelas untuk
     * siswa, jam pribadi guru (menu "Jam Guru") lalu jam kelas yang diampu
     * untuk wali kelas, atau tengah hari bila tidak ada acuan.
     */
    private function tebakJenis(Attendance $attendance): string
    {
        $waktu = $this->waktu($attendance);

        if ($waktu === null) {
            return 'masuk';
        }

        $titikTengah = $this->titikTengahOrang($attendance->employee_id);

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
     * Titik tengah rentang jam orang yang tap. Untuk siswa memakai jam kelas;
     * untuk guru memakai jam pribadi bila ada, jika tidak memakai jam kelas
     * yang diampu (wali kelas). Null bila tidak ada acuan (guru/tendik biasa).
     */
    private function titikTengahOrang(?string $employeeId): ?string
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
            return JamKelas::titikTengah($siswa->kelas?->nama_kelas);
        }

        $guru = Guru::with('kelasWali')
            ->where('wajah_id_zkteco', $employeeId)
            ->orWhere('pin', $employeeId)
            ->orWhere('rfid_kartu', $employeeId)
            ->first();

        if ($guru === null) {
            return null;
        }

        // Jam pribadi guru lebih diutamakan daripada jam kelas yang diampu.
        $pribadi = JamGuru::titikTengah($guru->jam_masuk, $guru->jam_pulang);

        return $pribadi ?? JamKelas::titikTengah($guru->kelasWali->first()?->nama_kelas);
    }
}
