<?php

namespace App\Support\Absensi;

use App\Models\PengaturanJam;
use Illuminate\Support\Carbon;

/**
 * Jam belajar per tingkat kelas.
 *
 * Sekolah memakai dua shift: kelas 1, 2, 5, 6 masuk pagi, sedangkan kelas 3
 * dan 4 masuk siang. Jam masuk inilah yang dipakai untuk menilai keterlambatan
 * (tap masuk setelah jam masuk = telat) sekaligus menebak jenis tap
 * masuk/pulang dari mesin yang hanya mengirim waktu mentah.
 *
 * Nilai dipakai dari tabel `pengaturan_jam` bila admin sudah mengaturnya lewat
 * menu "Atur Jam"; jika belum, dipakai jadwal bawaan di konstanta JADWAL.
 */
class JamKelas
{
    /**
     * Jadwal bawaan bila admin belum mengatur apa pun.
     *
     * @var array<int, array{masuk: string, pulang: string}>
     */
    private const JADWAL = [
        1 => ['masuk' => '06:30', 'pulang' => '10:30'],
        2 => ['masuk' => '06:30', 'pulang' => '10:30'],
        3 => ['masuk' => '12:30', 'pulang' => '16:30'],
        4 => ['masuk' => '12:30', 'pulang' => '16:30'],
        5 => ['masuk' => '06:30', 'pulang' => '11:30'],
        6 => ['masuk' => '06:30', 'pulang' => '11:30'],
    ];

    /**
     * Cache jadwal efektif (bawaan yang sudah ditimpa pengaturan DB).
     *
     * @var array<int, array{masuk: string, pulang: string}>
     */
    private static array $efektif = [];

    private static bool $sudahDimuat = false;

    /**
     * Buang cache supaya pengaturan terbaru langsung terpakai.
     */
    public static function resetCache(): void
    {
        self::$efektif = [];
        self::$sudahDimuat = false;
    }

    /**
     * Tingkat kelas yang punya jadwal (1..6).
     *
     * @return array<int, int>
     */
    public static function tingkatTersedia(): array
    {
        return array_keys(self::JADWAL);
    }

    /**
     * Jadwal bawaan satu tingkat, tanpa pengaturan DB.
     *
     * @return array{masuk: string, pulang: string}|null
     */
    public static function defaultTingkat(int $tingkat): ?array
    {
        return self::JADWAL[$tingkat] ?? null;
    }

    /**
     * Jadwal bawaan seluruh tingkat.
     *
     * @return array<int, array{masuk: string, pulang: string}>
     */
    public static function defaultSemua(): array
    {
        return self::JADWAL;
    }

    /**
     * Jadwal efektif seluruh tingkat: bawaan yang sudah ditimpa pengaturan DB.
     *
     * @return array<int, array{masuk: string, pulang: string}>
     */
    public static function semua(): array
    {
        return self::efektif();
    }

    /**
     * @return array<int, array{masuk: string, pulang: string}>
     */
    private static function efektif(): array
    {
        if (self::$sudahDimuat) {
            return self::$efektif;
        }

        self::$sudahDimuat = true;
        self::$efektif = self::JADWAL;

        // Tabel bisa belum ada saat migrasi pertama; dalam kondisi itu cukup
        // memakai jadwal bawaan.
        try {
            foreach (PengaturanJam::where('kategori', 'kelas')->get() as $row) {
                if ($row->tingkat === null || $row->jam_masuk === null || $row->jam_pulang === null) {
                    continue;
                }

                self::$efektif[(int) $row->tingkat] = [
                    'masuk' => substr((string) $row->jam_masuk, 0, 5),
                    'pulang' => substr((string) $row->jam_pulang, 0, 5),
                ];
            }
        } catch (\Throwable $e) {
            // Diamkan: pakai jadwal bawaan.
        }

        return self::$efektif;
    }

    /**
     * Tingkat kelas dari nama seperti "1 A", "4B", atau "Kelas 6".
     * Angka pertama pada nama dianggap tingkat.
     */
    public static function tingkat(?string $namaKelas): ?int
    {
        if ($namaKelas === null || trim($namaKelas) === '') {
            return null;
        }

        return preg_match('/\d+/', $namaKelas, $cocok) === 1 ? (int) $cocok[0] : null;
    }

    /**
     * @return array{masuk: string, pulang: string}|null
     */
    public static function jadwal(?string $namaKelas): ?array
    {
        $tingkat = self::tingkat($namaKelas);

        return $tingkat === null ? null : (self::efektif()[$tingkat] ?? null);
    }

    public static function jamMasuk(?string $namaKelas): ?string
    {
        return self::jadwal($namaKelas)['masuk'] ?? null;
    }

    public static function jamPulang(?string $namaKelas): ?string
    {
        return self::jadwal($namaKelas)['pulang'] ?? null;
    }

    /**
     * Titik tengah rentang belajar (mis. kelas 1: 06:30-10:30 -> 08:30).
     *
     * Tap sebelum titik tengah dianggap "masuk", sesudahnya "pulang". Ini
     * pengganti aturan lama "jam >= 12 = pulang" yang salah untuk kelas siang.
     */
    public static function titikTengah(?string $namaKelas): ?string
    {
        $jadwal = self::jadwal($namaKelas);

        if ($jadwal === null) {
            return null;
        }

        $tengah = intdiv(self::menit($jadwal['masuk']) + self::menit($jadwal['pulang']), 2);

        return sprintf('%02d:%02d', intdiv($tengah, 60), $tengah % 60);
    }

    /**
     * True bila tap masuk terjadi setelah jam masuk kelas (tanpa toleransi).
     * Selalu false bila kelas tidak punya jadwal (guru non-wali).
     */
    public static function terlambat(Carbon|string $timestamp, ?string $namaKelas): bool
    {
        $jamMasuk = self::jamMasuk($namaKelas);

        if ($jamMasuk === null) {
            return false;
        }

        $tap = $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);
        $batas = Carbon::parse($tap->toDateString().' '.$jamMasuk);

        return $tap->greaterThan($batas);
    }

    private static function menit(string $jam): int
    {
        [$jam, $menit] = array_map('intval', explode(':', $jam));

        return $jam * 60 + $menit;
    }
}
