<?php

namespace App\Support\Roster;

use Illuminate\Support\Str;

/**
 * Mengubah file sumber mentah menjadi struktur data siap simpan.
 */
class RosterExtractor
{
    /**
     * Daftar siswa + kelas + wali kelas dari file absensi (.xls SpreadsheetML).
     *
     * @return array{
     *     kelas: array<int, array{nama: string, wali: string|null, jumlah: int}>,
     *     siswa: array<int, array{nama: string, kelas: string, jk: string, nisn: string, nis: string, pin: string}>,
     *     duplikat: array<string, array<int, string>>,
     *     sel_absensi: int
     * }
     */
    public static function dariAbsensi(string $path): array
    {
        $sheets = SourceFileReader::xls($path);

        $kelas = [];
        $siswa = [];
        $duplikat = [];
        $selAbsensi = 0;

        foreach ($sheets as $sheetName => $rows) {
            // "1. 1 A" -> "1 A"
            $namaKelas = $sheetName;
            if (preg_match('/^\d+\.\s*(.+)$/', $sheetName, $match) === 1) {
                $namaKelas = trim($match[1]);
            }

            $wali = null;

            foreach ($rows as $cells) {
                foreach ($cells as $value) {
                    if ($value !== null && stripos($value, 'Wali Kelas:') !== false) {
                        if (preg_match('/Wali Kelas:\s*(.+?)\s*$/i', $value, $m) === 1) {
                            $wali = trim($m[1]);
                        }
                    }
                }

                // Kolom: 1=urut, 2="NISN / NIS", 3=nama, 4=L/P, 5..=tanda absensi
                $ids = trim((string) ($cells[2] ?? ''));
                $nama = trim((string) ($cells[3] ?? ''));

                if ($ids === '' || $nama === '' || preg_match('/^\d/', $ids) !== 1) {
                    continue;
                }

                $parts = array_map('trim', explode('/', $ids));
                $nisn = $parts[0] ?? '';
                $nis = $parts[1] ?? '';

                $key = $nisn !== '' ? $nisn : $nis;

                if (isset($siswa[$key])) {
                    $duplikat[$key][] = $namaKelas.' / '.$nama;

                    continue;
                }

                $siswa[$key] = [
                    'nama' => $nama,
                    'kelas' => $namaKelas,
                    'jk' => trim((string) ($cells[4] ?? '')),
                    'nisn' => $nisn,
                    'nis' => $nis,
                    // Kolom siswa.nis bersifat NOT NULL UNIQUE, jadi NIS dipakai dulu,
                    // lalu NISN sebagai cadangan untuk siswa yang belum punya NIS.
                    'pin' => $nis !== '' ? $nis : $nisn,
                ];

                for ($column = 5; $column <= 35; $column++) {
                    if (trim((string) ($cells[$column] ?? '')) !== '') {
                        $selAbsensi++;
                    }
                }
            }

            if (! isset($kelas[$namaKelas])) {
                $kelas[$namaKelas] = ['nama' => $namaKelas, 'wali' => $wali, 'jumlah' => 0];
            }
            $kelas[$namaKelas]['wali'] = $wali ?: $kelas[$namaKelas]['wali'];
        }

        foreach ($siswa as $row) {
            $kelas[$row['kelas']]['jumlah']++;
        }

        ksort($kelas);

        return [
            'kelas' => array_values($kelas),
            'siswa' => array_values($siswa),
            'duplikat' => $duplikat,
            'sel_absensi' => $selAbsensi,
        ];
    }

    /**
     * Daftar pegawai (guru + tendik) dari file daftar Dapodik (.xlsx).
     *
     * @return array<int, array{nama: string, nuptk: string, nip: string, nik: string, jenis_ptk: string, is_tendik: bool}>
     */
    public static function dariDaftarPtk(string $path, bool $isTendik): array
    {
        $rows = SourceFileReader::xlsx($path);

        // Cari baris header untuk memetakan kolom secara dinamis.
        $headerIndex = null;
        foreach ($rows as $index => $row) {
            foreach ($row as $value) {
                if (strcasecmp($value, 'NUPTK') === 0) {
                    $headerIndex = $index;
                    break 2;
                }
            }
        }

        if ($headerIndex === null) {
            return [];
        }

        $map = [];
        foreach ($rows[$headerIndex] as $column => $label) {
            $map[strtolower($label)] = $column;
        }

        $pick = function (array $row, array $keys) use ($map): string {
            foreach ($keys as $key) {
                if (isset($map[$key], $row[$map[$key]])) {
                    return $row[$map[$key]];
                }
            }

            return '';
        };

        $ptk = [];

        foreach (array_slice($rows, $headerIndex + 1) as $row) {
            $nama = $pick($row, ['nama', 'nama pts', 'nama lengkap']);
            if ($nama === '') {
                continue;
            }

            $ptk[] = [
                'nama' => $nama,
                'nuptk' => $pick($row, ['nuptk']),
                'nip' => $pick($row, ['nip']),
                'nik' => $pick($row, ['nik']),
                'jenis_ptk' => $pick($row, ['jenis ptk']),
                'is_tendik' => $isTendik,
            ];
        }

        return $ptk;
    }

    /**
     * Samakan nama wali kelas dengan nama pegawai secara case-insensitive.
     *
     * @param  array<int, array{nama: string, ...}>  $ptk
     * @return array<string, int> nama wali (lowercase) => indeks PTK
     */
    public static function indeksWali(array $ptk): array
    {
        $index = [];

        foreach ($ptk as $position => $row) {
            $index[Str::lower(trim($row['nama']))] = $position;
        }

        return $index;
    }

    /**
     * Daftar siswa dari file Dapodik "Detail Sekolah - Data Pokok Pendidikan" (.xlsx).
     *
     * Kolom yang dipakai: Nama Lengkap, L/P, NISN, Tingkat, dan Rombel. Ekspor
     * Dapodik sering membekukan NISN/NIK dengan karakter zero-width (mis.
     * U+200C), sehingga NISN dibersihkan menjadi digit saja. Rombel "1 A"
     * dinormalkan menjadi "1A" agar cocok dengan nama kelas di database.
     * Baris tanpa nama atau rombel dilewati (mis. baris rekap di bawah tabel).
     *
     * @return array<int, array{nama: string, nisn: string, jk: string, tingkat: string, rombel: string}>
     */
    public static function dariDetailSekolah(string $path): array
    {
        $rows = SourceFileReader::xlsx($path);

        // Cari baris header supaya pemetaan kolom tidak bergantung urutan.
        $headerIndex = null;
        foreach ($rows as $index => $row) {
            foreach ($row as $value) {
                if (strcasecmp(self::bersihTeks((string) $value), 'NISN') === 0) {
                    $headerIndex = $index;
                    break 2;
                }
            }
        }

        if ($headerIndex === null) {
            return [];
        }

        $map = [];
        foreach ($rows[$headerIndex] as $column => $label) {
            $map[Str::lower(self::bersihTeks((string) $label))] = $column;
        }

        $pick = function (array $row, array $keys) use ($map): string {
            foreach ($keys as $key) {
                if (isset($map[$key], $row[$map[$key]])) {
                    return (string) $row[$map[$key]];
                }
            }

            return '';
        };

        $siswa = [];

        foreach (array_slice($rows, $headerIndex + 1) as $row) {
            $nama = self::bersihTeks($pick($row, ['nama lengkap', 'nama']));
            $rombel = $pick($row, ['rombel']);

            if ($nama === '' || self::bersihTeks($rombel) === '') {
                continue;
            }

            $siswa[] = [
                'nama' => $nama,
                'nisn' => self::bersihNisn($pick($row, ['nisn'])),
                'jk' => self::bersihTeks($pick($row, ['l/p', 'jk', 'jenis kelamin'])),
                'tingkat' => self::bersihTeks($pick($row, ['tingkat'])),
                'rombel' => self::kunciRombel($rombel),
            ];
        }

        return $siswa;
    }

    /**
     * Buang karakter tak terlihat dan rapikan spasi pada teks dari Excel.
     *
     * Zero-width space/joiner/non-joiner dan NBSP dibuang/diganti supaya nama
     * yang tampak sama benar-benar dianggap sama.
     */
    public static function bersihTeks(string $teks): string
    {
        $tanpaZeroWidth = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $teks);
        if ($tanpaZeroWidth === null) {
            $tanpaZeroWidth = $teks;
        }

        // NBSP tidak termasuk \s sebelum mode UCP, jadi disebut eksplisit.
        $rapikan = preg_replace('/[\s\x{00A0}]+/u', ' ', $tanpaZeroWidth);
        if ($rapikan === null) {
            $rapikan = $tanpaZeroWidth;
        }

        return trim($rapikan);
    }

    /**
     * Kunci pembanding nama: huruf kecil, spasi dirapikan, zero-width dibuang.
     */
    public static function normalisasiNama(string $nama): string
    {
        return Str::lower(self::bersihTeks($nama));
    }

    /**
     * Kunci pembanding rombel/kelas: tanpa spasi, huruf besar ("1 a" -> "1A").
     */
    public static function kunciRombel(string $rombel): string
    {
        $hasil = preg_replace('/\s+/', '', strtoupper(self::bersihTeks($rombel)));

        return $hasil ?? '';
    }

    /**
     * Ambil digit dari NISN/NIS/NIK (membuang zero-width dan pemisah lain).
     */
    public static function bersihNisn(string $nilai): string
    {
        $hasil = preg_replace('/\D+/', '', $nilai);

        return $hasil ?? '';
    }
}
