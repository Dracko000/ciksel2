<?php

namespace App\Livewire\Admin;

use App\Support\Absensi\AbsensiRekap;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Rekap presensi guru & tenaga kependidikan saja.
 *
 * Memakai AbsensiRekap yang sama dengan Monitor Absensi (peran = guru),
 * sehingga tabel di layar dan file yang diunduh tidak pernah berbeda.
 * Guru yang belum punya ID mesin (wajah_id_zkteco) tidak muncul di sini.
 */
class PresensiGuru extends Component
{
    use WithPagination;

    public $dateFilter;

    /** Pencarian nama atau NIP guru. */
    public $search = '';

    /** Jumlah baris per halaman. */
    public const PER_PAGE = 25;

    public function mount(): void
    {
        $this->dateFilter = date('Y-m-d');
    }

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $rekap = new AbsensiRekap;

        $taps = $rekap->tapHari($this->dateFilter);
        $groups = $rekap->orang('', 'guru', $taps->keys()->all());
        $rows = $rekap->baris($groups, $taps);

        // Ringkasan donut selalu mencerminkan seluruh tanggal, bukan hasil
        // pencarian, supaya grafiknya tidak berubah saat mencari orang.
        // Tepat waktu dan terlambat dipisah agar donut menampilkan tiga porsi.
        $hadirTepatTanggal = collect($rows)->where('status', 'Hadir')->count();
        $terlambatTanggal = collect($rows)->where('status', 'Terlambat')->count();
        $hadirTanggal = $hadirTepatTanggal + $terlambatTanggal;
        $alphaTanggal = count($rows) - $hadirTanggal;

        if (trim($this->search) !== '') {
            $cari = mb_strtolower(trim($this->search));
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => str_contains(mb_strtolower($row['nama']), $cari)
                    || str_contains(mb_strtolower((string) $row['nis']), $cari)
            ));
        }

        $hadir = collect($rows)->whereIn('status', ['Hadir', 'Terlambat'])->count();
        $terlambat = collect($rows)->where('status', 'Terlambat')->count();

        // Paginasi di memori, sama seperti Monitor Absensi: sumbernya sudah
        // berupa baris rekap (puluhan), bukan query log tap.
        $page = max(1, (int) $this->getPage('page'));
        $presensi = new LengthAwarePaginator(
            collect($rows)->forPage($page, self::PER_PAGE),
            count($rows),
            self::PER_PAGE,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        return view('livewire.admin.presensi-guru', [
            'presensi' => $presensi,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'hadirTepatTanggal' => $hadirTepatTanggal,
            'terlambatTanggal' => $terlambatTanggal,
            'alpha' => count($rows) - $hadir,
            'hadirTanggal' => $hadirTanggal,
            'alphaTanggal' => $alphaTanggal,
        ])->layout('components.layouts.app');
    }
}
