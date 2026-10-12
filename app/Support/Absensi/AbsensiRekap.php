<?php

namespace App\Support\Absensi;

use App\Models\Guru;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekap absensi per orang.
 *
 * Mesin ZKTeco hanya mengirim "tap" mentah (satu baris per pemindaian), yang
 * untuk satu hari bisa ratusan baris dan sulit dibaca. Service ini
 * mengubah tap-tap itu menjadi satu baris per orang: jam masuk (tap pertama),
 * jam pulang (tap terakhir), jumlah tap, dan status.
 *
 * Dipakai bersama oleh halaman Monitor Absensi (Livewire) dan export CSV
 * harian/mingguan/bulanan, supaya isi tabel di layar dan isi file unduhan
 * tidak pernah berbeda.
 */
class AbsensiRekap
{
    public const KELOMPOK_GURU = 'Guru & Tendik';

    public const KELOMPOK_TANPA_KELAS = 'Tanpa Kelas';

    public const KELOMPOK_TIDAK_DIKENAL = 'Tidak Dikenal (Unregistered)';

    /**
     * Daftar orang yang masuk lingkup filter, dikelompokkan per kelas
     * (lalu Guru & Tendik, lalu tap yang tidak dikenal).
     *
     * @param  array<int, string|int>  $tapIds  employee_id yang punya tap pada
     *                                          periode; dipakai untuk menemukan
     *                                          tap tak dikenal.
     * @return array<int, array{label: string, items: array<int, array{employee_id: string, nama: string, nis: string, kelas: string|null, tipe: string}>}>
     */
    public function orang(string $kelasFilter = '', string $roleFilter = '', array $tapIds = []): array
    {
        $groups = [];
        $dikenal = [];

        // Filter kelas berlaku untuk siswa saja; siswa tanpa ZKTeco ID tidak
        // mungkin terlacak mesin, jadi tidak ikut dihitung (dan kelasnya tidak
        // ditawarkan sebagai filter di halaman).
        if ($roleFilter !== 'guru') {
            $siswas = Siswa::with('kelas')
                ->whereNotNull('wajah_id_zkteco')
                ->when($kelasFilter !== '', fn ($query) => $query->where('kelas_id', $kelasFilter))
                ->orderBy('nama')
                ->get();

            foreach ($siswas as $siswa) {
                $dikenal[(string) $siswa->wajah_id_zkteco] = true;
            }

            $perKelas = $siswas->groupBy(fn ($siswa) => $siswa->kelas?->nama_kelas ?? self::KELOMPOK_TANPA_KELAS);

            $labels = $perKelas->keys()
                ->filter(fn ($label) => $label !== self::KELOMPOK_TANPA_KELAS)
                ->sort()
                ->values()
                ->all();

            if ($perKelas->has(self::KELOMPOK_TANPA_KELAS)) {
                $labels[] = self::KELOMPOK_TANPA_KELAS;
            }

            foreach ($labels as $label) {
                $groups[] = [
                    'label' => $label,
                    'items' => $perKelas[$label]
                        ->map(fn ($siswa) => [
                            'employee_id' => (string) $siswa->wajah_id_zkteco,
                            'nama' => $siswa->nama,
                            'nis' => (string) ($siswa->nis ?? '-'),
                            'kelas' => $siswa->kelas?->nama_kelas,
                            'kelas_wali' => null,
                            'tipe' => 'siswa',
                        ])
                        ->values()
                        ->all(),
                ];
            }
        }

        // Guru/tendik tidak punya kelas, jadi ketika filter kelas aktif mereka
        // memang tidak boleh muncul.
        if ($roleFilter !== 'siswa' && $kelasFilter === '') {
            $gurus = Guru::with('kelasWali')->whereNotNull('wajah_id_zkteco')->orderBy('nama')->get();

            foreach ($gurus as $guru) {
                $dikenal[(string) $guru->wajah_id_zkteco] = true;
            }

            if ($gurus->isNotEmpty()) {
                $groups[] = [
                    'label' => self::KELOMPOK_GURU,
                    'items' => $gurus->map(fn ($guru) => [
                        'employee_id' => (string) $guru->wajah_id_zkteco,
                        'nama' => $guru->nama,
                        'nis' => (string) ($guru->nip ?? '-'),
                        'kelas' => null,
                        'kelas_wali' => $guru->kelasWali->first()?->nama_kelas,
                        'tipe' => 'guru',
                    ])->values()->all(),
                ];
            }
        }

        // Tap dari wajah yang tidak terdaftar hanya relevan ketika tidak ada
        // filter sama sekali (filter kelas/peran memang menyaringnya keluar).
        if ($kelasFilter === '' && $roleFilter === '' && $tapIds !== []) {
            $asing = array_values(array_diff(array_map('strval', $tapIds), array_keys($dikenal)));

            sort($asing);

            if ($asing !== []) {
                $groups[] = [
                    'label' => self::KELOMPOK_TIDAK_DIKENAL,
                    'items' => array_map(fn ($id) => [
                        'employee_id' => $id,
                        'nama' => self::KELOMPOK_TIDAK_DIKENAL,
                        'nis' => $id,
                        'kelas' => null,
                        'kelas_wali' => null,
                        'tipe' => 'lain',
                    ], $asing),
                ];
            }
        }

        return $groups;
    }

    /**
     * Ringkasan tap satu tanggal, diagregasi per orang.
     *
     * @return Collection<int|string, object{employee_id: int|string, masuk: string, pulang: string, tap: int}>
     */
    public function tapHari(string $tanggal): Collection
    {
        // Kehadiran dihitung dari log "masuk" saja supaya satu orang pada satu
        // hari tidak terhitung dua kali karena tap pulang. Jam pulang tetap
        // diambil (log "pulang" terakhir) untuk ditampilkan.
        $start = $tanggal . ' 00:00:00';
        $end = $tanggal . ' 23:59:59';

        return DB::table('attendances')
            ->whereBetween('timestamp', [$start, $end])
            ->groupBy('employee_id')
            ->selectRaw(
                'employee_id, '
                ."MIN(CASE WHEN jenis = 'masuk' THEN timestamp END) as masuk, "
                ."MAX(CASE WHEN jenis = 'pulang' THEN timestamp END) as pulang, "
                ."SUM(CASE WHEN jenis = 'masuk' THEN 1 ELSE 0 END) as tap"
            )
            ->get()
            ->keyBy('employee_id');
    }

    /**
     * Ringkasan tap per orang per hari untuk satu rentang tanggal
     * (dipakai export mingguan/bulanan).
     *
     * @return array<string, array<string, object{masuk: string, pulang: string, tap: int}>>
     */
    public function tapRentang(string $mulai, string $selesai): array
    {
        $rows = DB::table('attendances')
            ->whereBetween('timestamp', [$mulai.' 00:00:00', $selesai.' 23:59:59'])
            ->groupByRaw('employee_id, DATE(timestamp)')
            ->selectRaw(
                'employee_id, DATE(timestamp) as tanggal, '
                ."MIN(CASE WHEN jenis = 'masuk' THEN timestamp END) as masuk, "
                ."MAX(CASE WHEN jenis = 'pulang' THEN timestamp END) as pulang, "
                ."SUM(CASE WHEN jenis = 'masuk' THEN 1 ELSE 0 END) as tap"
            )
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[(string) $row->employee_id][(string) $row->tanggal] = $row;
        }

        return $map;
    }

    /**
     * Gabungkan daftar orang dengan ringkasan tap menjadi baris rekap.
     *
     * @param  array<int, array{label: string, items: array<int, array<string, mixed>>}>  $groups
     * @param  Collection<int|string, object{masuk: string, pulang: string, tap: int}>|array<string, array<string, object{masuk: string, pulang: string, tap: int}>>  $taps
     * @return array<int, array<string, mixed>>
     */
    public function baris(array $groups, Collection|array $taps, ?string $tanggal = null): array
    {
        $baris = [];

        foreach ($groups as $group) {
            foreach ($group['items'] as $orang) {
                $tap = $this->cariTap($taps, $orang['employee_id'], $tanggal);
                $adaTap = $tap !== null;
                // Hadir hanya jika ada log masuk; log pulang cukup ditampilkan.
                $adaMasuk = $adaTap && $tap->masuk !== null;

                // Acuan jam masuk: kelas siswa, atau kelas yang diampu wali
                // kelas. Guru/tendik biasa tidak punya acuan sehingga tidak
                // pernah dinilai telat.
                $kelasAcuan = $orang['kelas'] ?? $orang['kelas_wali'] ?? null;
                $jamMasuk = JamKelas::jamMasuk($kelasAcuan);

                if (! $adaMasuk) {
                    $status = 'Alpha';
                } elseif (JamKelas::terlambat((string) $tap->masuk, $kelasAcuan)) {
                    $status = 'Terlambat';
                } else {
                    $status = 'Hadir';
                }

                $baris[] = [
                    'kelompok' => $group['label'],
                    'employee_id' => $orang['employee_id'],
                    'nama' => $orang['nama'],
                    'nis' => $orang['nis'],
                    'kelas' => $orang['kelas'],
                    'tipe' => $orang['tipe'],
                    'masuk' => $adaMasuk ? $this->jam($tap->masuk) : null,
                    'pulang' => $adaTap && $tap->pulang !== null ? $this->jam($tap->pulang) : null,
                    'jumlahTap' => $adaMasuk ? (int) $tap->tap : 0,
                    'jamMasuk' => $jamMasuk,
                    'status' => $status,
                ];
            }
        }

        return $baris;
    }

    /**
     * @param  Collection<int|string, object{masuk: string, pulang: string, tap: int}>|array<string, array<string, object{masuk: string, pulang: string, tap: int}>>  $taps
     */
    private function cariTap(Collection|array $taps, string $employeeId, ?string $tanggal): ?object
    {
        if ($taps instanceof Collection) {
            return $taps->get($employeeId);
        }

        return $tanggal !== null ? ($taps[$employeeId][$tanggal] ?? null) : null;
    }

    private function jam(string $timestamp): string
    {
        return Carbon::parse($timestamp)->format('H:i');
    }
}
