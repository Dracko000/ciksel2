<?php

namespace App\Support\Absensi;

use App\Models\Device;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan RFID/ID mesin yang belum terhubung ke siswa maupun guru.
 *
 * Halaman Presensi hanya menampilkan tap asing pada satu tanggal. Halaman
 * "RFID Belum Terdaftar" mengumpulkan semuanya (default: seluruh riwayat)
 * supaya admin bisa mencocokkan satu RFID ke satu pengguna, sekali saja.
 *
 * Definisi "sudah terdaftar" sengaja memakai AbsensiRekap::semuaIdDikenal()
 * agar daftar di sini tidak pernah berbeda dengan rekap presensi.
 */
class RfidBelumTerdaftar
{
    public function __construct(private readonly AbsensiRekap $rekap = new AbsensiRekap) {}

    /**
     * Tap asing (tidak dikenal) diagregasi per RFID.
     *
     * @param  int|null  $hari  Batasi ke N hari terakhir; null = semua riwayat.
     * @return array<int, array{employee_id: string, mesin: array<int, array{nama: string, sn: string, jumlah: int}>, jumlahTap: int, pertama: string, terakhir: string, jamMasuk: string|null, jamPulang: string|null}>
     */
    public function ringkasan(?int $hari = null): array
    {
        $dikenal = $this->rekap->semuaIdDikenal();
        $namaMesin = Device::pluck('nama', 'no_sn');

        $rows = DB::table('attendances')
            ->when(
                $hari !== null,
                fn ($query) => $query->where('timestamp', '>=', now()->subDays($hari)->startOfDay())
            )
            ->groupBy('employee_id')
            ->selectRaw(
                'employee_id, COUNT(*) as jumlah_tap, '
                .'MIN(timestamp) as pertama, MAX(timestamp) as terakhir, '
                ."MIN(CASE WHEN jenis = 'masuk' THEN TIME(timestamp) END) as jam_masuk, "
                ."MAX(CASE WHEN jenis = 'pulang' THEN TIME(timestamp) END) as jam_pulang"
            )
            ->orderByDesc('terakhir')
            ->get();

        // Rincian per mesin: satu RFID bisa tap di lebih dari satu mesin.
        $perangkat = DB::table('attendances')
            ->when(
                $hari !== null,
                fn ($query) => $query->where('timestamp', '>=', now()->subDays($hari)->startOfDay())
            )
            ->groupBy('employee_id', 'sn')
            ->selectRaw('employee_id, sn, COUNT(*) as jumlah')
            ->orderByDesc('jumlah')
            ->orderBy('sn')
            ->get()
            ->groupBy('employee_id');

        $hasil = [];

        foreach ($rows as $row) {
            $employeeId = trim((string) $row->employee_id);

            // RFID kosong tidak bisa dicocokkan ke siapa pun.
            if ($employeeId === '' || isset($dikenal[$employeeId])) {
                continue;
            }

            $mesin = [];

            foreach ($perangkat->get($employeeId, collect()) as $device) {
                $sn = (string) $device->sn;

                $mesin[] = [
                    'sn' => $sn,
                    'nama' => (string) ($namaMesin[$sn] ?? $sn),
                    'jumlah' => (int) $device->jumlah,
                ];
            }

            $hasil[] = [
                'employee_id' => $employeeId,
                'mesin' => $mesin,
                'jumlahTap' => (int) $row->jumlah_tap,
                'pertama' => Carbon::parse($row->pertama)->format('d/m/Y H:i'),
                'terakhir' => Carbon::parse($row->terakhir)->format('d/m/Y H:i'),
                'jamMasuk' => $row->jam_masuk !== null ? substr((string) $row->jam_masuk, 0, 5) : null,
                'jamPulang' => $row->jam_pulang !== null ? substr((string) $row->jam_pulang, 0, 5) : null,
            ];
        }

        return $hasil;
    }
}
