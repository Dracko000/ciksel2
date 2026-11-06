<?php

namespace App\Support\Absensi;

use Illuminate\Support\Carbon;

/**
 * Jam masuk/pulang pribadi tiap guru & tenaga kependidikan.
 *
 * Berbeda dengan JamKelas yang selalu punya jadwal bawaan per tingkat, jam
 * guru tidak punya nilai bawaan: guru hanya dinilai terlambat apabila admin
 * sudah mengatur jam pribadinya lewat menu "Jam Guru". Guru yang belum diatur
 * (kolom `jam_masuk` di tabel `guru` kosong) tidak dinilai telat.
 *
 * Nilai jam diambil langsung dari model Guru, jadi tidak ada cache statis.
 */
class JamGuru
{
    /**
     * Normalisasi jam dari database ('HH:MM:SS') atau form ('HH:MM') menjadi
     * 'HH:MM'. Mengembalikan null bila kosong.
     */
    public static function jam(?string $nilai): ?string
    {
        if ($nilai === null || trim($nilai) === '') {
            return null;
        }

        return substr($nilai, 0, 5);
    }

    /**
     * True bila tap masuk terjadi setelah jam masuk pribadi guru. Selalu false
     * bila jam masuk guru belum diatur.
     */
    public static function terlambat(Carbon|string $timestamp, ?string $jamMasuk): bool
    {
        $jamMasuk = self::jam($jamMasuk);

        if ($jamMasuk === null) {
            return false;
        }

        $tap = $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);
        $batas = Carbon::parse($tap->toDateString().' '.$jamMasuk);

        return $tap->greaterThan($batas);
    }

    /**
     * Titik tengah rentang jam pribadi guru (mis. 07:00-14:00 -> 10:30), dipakai
     * menebak tap masuk/pulang dari mesin. Null bila salah satu jam belum diatur.
     */
    public static function titikTengah(?string $jamMasuk, ?string $jamPulang): ?string
    {
        $jamMasuk = self::jam($jamMasuk);
        $jamPulang = self::jam($jamPulang);

        if ($jamMasuk === null || $jamPulang === null) {
            return null;
        }

        $tengah = intdiv(self::menit($jamMasuk) + self::menit($jamPulang), 2);

        return sprintf('%02d:%02d', intdiv($tengah, 60), $tengah % 60);
    }

    private static function menit(string $jam): int
    {
        [$jam, $menit] = array_map('intval', explode(':', $jam));

        return $jam * 60 + $menit;
    }
}
