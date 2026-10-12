<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Support\Absensi\AbsensiRekap;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unduh rekap absensi dalam bentuk CSV.
 *
 * Tiga periode ditawarkan dari halaman Monitor Absensi: harian (satu hari),
 * mingguan (Senin-Minggu minggu yang mengandung tanggal pilihan), dan
 * bulanan (satu bulan). Isinya memakai AbsensiRekap yang sama dengan yang
 * ditampilkan di layar, jadi file unduhan dan tabel tidak pernah berbeda.
 */
class AbsensiExportController extends Controller
{
    /** Singkatan hari Indonesia untuk kepala kolom tanggal. */
    private const HARI = [
        'Mon' => 'Sen',
        'Tue' => 'Sel',
        'Wed' => 'Rab',
        'Thu' => 'Kam',
        'Fri' => 'Jum',
        'Sat' => 'Sab',
        'Sun' => 'Min',
    ];

    private const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __invoke(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'periode' => ['nullable', Rule::in(['harian', 'mingguan', 'bulanan'])],
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'peran' => ['nullable', Rule::in(['siswa', 'guru'])],
        ]);

        $periode = $validated['periode'] ?? 'harian';
        $kelasFilter = (string) ($validated['kelas_id'] ?? '');
        $peran = (string) ($validated['peran'] ?? '');
        $tanggal = Carbon::parse($validated['tanggal'] ?? date('Y-m-d'));

        [$mulai, $selesai] = match ($periode) {
            'mingguan' => [$tanggal->copy()->startOfWeek(), $tanggal->copy()->endOfWeek()],
            'bulanan' => [$tanggal->copy()->startOfMonth(), $tanggal->copy()->endOfMonth()],
            default => [$tanggal->copy(), $tanggal->copy()],
        };

        [$header, $baris] = $periode === 'harian'
            ? $this->harian($mulai, $kelasFilter, $peran)
            : $this->rentang($mulai, $selesai, $kelasFilter, $peran);

        $kelasLabel = $kelasFilter !== ''
            ? (Kelas::find($kelasFilter)?->nama_kelas ?? 'Kelas terpilih')
            : 'Semua Kelas';

        $judul = match ($peran) {
            'guru' => 'Rekap Presensi Guru',
            'siswa' => 'Rekap Presensi Siswa',
            default => 'Rekap Absensi',
        };

        $lines = [
            [$judul.' '.ucfirst($periode)],
            ['Periode', $this->labelPeriode($periode, $mulai, $selesai)],
            ['Kelas', $kelasLabel],
            ['Peran', $peran === 'siswa' ? 'Siswa' : ($peran === 'guru' ? 'Guru/Tendik' : 'Semua Peran')],
            ['Diunduh', now()->format('d-m-Y H:i')],
            [],
            $header,
            ...$baris,
        ];

        $prefix = $peran === 'guru' ? 'presensi-guru' : 'absensi';

        $filename = match ($periode) {
            'mingguan' => $prefix.'-mingguan_'.$mulai->format('Y-m-d').'_'.$selesai->format('Y-m-d').'.csv',
            'bulanan' => $prefix.'-bulanan_'.$mulai->format('Y-m').'.csv',
            default => $prefix.'-harian_'.$mulai->format('Y-m-d').'.csv',
        };

        return response()->streamDownload(function () use ($lines) {
            $out = fopen('php://output', 'w');
            // BOM supaya Excel membaca UTF-8 sebagai Indonesia, bukan mojibake.
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($lines as $line) {
                fputcsv($out, $line, ',', '"', '\\');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, string|int>>}
     */
    private function harian(Carbon $tanggal, string $kelasFilter, string $peran): array
    {
        $rekap = new AbsensiRekap();

        $taps = $rekap->tapHari($tanggal->toDateString());
        $groups = $rekap->orang($kelasFilter, $peran, $taps->keys()->all());

        $header = ['Kelas', 'Nama', 'NIS/NIK/NIP', 'ID Mesin', 'Jam Masuk', 'Jam Pulang', 'Jumlah Masuk', 'Status'];

        $baris = array_map(fn (array $row) => [
            $row['kelompok'],
            $row['nama'],
            $row['nis'],
            $row['employee_id'],
            $row['masuk'] ?? '-',
            $row['pulang'] ?? '-',
            $row['jumlahTap'],
            $row['status'],
        ], $rekap->baris($groups, $taps));

        return [$header, $baris];
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, string|int>>}
     */
    private function rentang(Carbon $mulai, Carbon $selesai, string $kelasFilter, string $peran): array
    {
        $rekap = new AbsensiRekap();

        $taps = $rekap->tapRentang($mulai->toDateString(), $selesai->toDateString());
        $groups = $rekap->orang($kelasFilter, $peran, array_keys($taps));

        $hari = [];
        for ($tanggal = $mulai->copy(); $tanggal->lte($selesai); $tanggal->addDay()) {
            $hari[] = $tanggal->copy();
        }

        $header = array_merge(
            ['Kelas', 'Nama', 'NIS/NIK/NIP', 'ID Mesin'],
            array_map(fn (Carbon $tgl) => self::HARI[$tgl->format('D')].' '.$tgl->format('d-m'), $hari),
            ['Hari Hadir']
        );

        $baris = [];

        foreach ($groups as $group) {
            foreach ($group['items'] as $orang) {
                $perTanggal = $taps[$orang['employee_id']] ?? [];
                $cells = [];
                $hadir = 0;

                foreach ($hari as $tgl) {
                    $tap = $perTanggal[$tgl->toDateString()] ?? null;

                    if ($tap === null) {
                        $cells[] = '-';
                        continue;
                    }

                    // Kehadiran dihitung dari log masuk saja; jam pulang tetap
                    // ditampilkan walaupun tanpa log masuk.
                    $masuk = $tap->masuk !== null ? Carbon::parse($tap->masuk)->format('H:i') : null;
                    $pulang = $tap->pulang !== null ? Carbon::parse($tap->pulang)->format('H:i') : null;

                    $cells[] = ($masuk ?? '-').'-'.($pulang ?? '-');

                    if ($masuk !== null) {
                        $hadir++;
                    }
                }

                $baris[] = array_merge([
                    $group['label'],
                    $orang['nama'],
                    $orang['nis'],
                    $orang['employee_id'],
                ], $cells, [$hadir]);
            }
        }

        return [$header, $baris];
    }

    private function labelPeriode(string $periode, Carbon $mulai, Carbon $selesai): string
    {
        return match ($periode) {
            'mingguan' => $mulai->format('d-m-Y').' s.d. '.$selesai->format('d-m-Y'),
            'bulanan' => self::BULAN[(int) $mulai->format('n')].' '.$mulai->format('Y'),
            default => self::HARI[$mulai->format('D')].', '.$mulai->format('d-m-Y'),
        };
    }
}
