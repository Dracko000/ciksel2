<?php

namespace App\Livewire\Admin;

use App\Models\Kelas;
use App\Support\Absensi\AbsensiRekap;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Rekap presensi siswa saja.
 *
 * Kembaran PresensiGuru, tetapi lingkupnya siswa (peran = siswa) dan bisa
 * disaring per kelas. Memakai AbsensiRekap yang sama dengan Monitor Absensi
 * supaya tabel dan file unduhan tidak pernah berbeda.
 */
class PresensiMurid extends Component
{
    use WithPagination;

    public $dateFilter;

    /** Filter kelas. String kosong = semua kelas. */
    public $kelasFilter = '';

    /** Pencarian nama atau NIS siswa. */
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

    public function updatingKelasFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->kelasFilter = '';
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $rekap = new AbsensiRekap;

        $taps = $rekap->tapHari($this->dateFilter);
        $groups = $rekap->orang((string) $this->kelasFilter, 'siswa', $taps->keys()->all());
        $rows = $rekap->baris($groups, $taps);
        $belumTerdaftar = $rekap->belumTerdaftar($taps);

        // Ringkasan donut selalu mencerminkan seluruh tanggal, bukan hasil
        // pencarian, supaya grafiknya tidak berubah saat menyaring.
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
        // berupa baris rekap (ratusan), bukan query log tap.
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

        $kelasList = Kelas::whereHas('siswa', fn ($query) => $query->whereNotNull('wajah_id_zkteco'))
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas']);

        return view('livewire.admin.presensi-murid', [
            'presensi' => $presensi,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'hadirTepatTanggal' => $hadirTepatTanggal,
            'terlambatTanggal' => $terlambatTanggal,
            'alpha' => count($rows) - $hadir,
            'hadirTanggal' => $hadirTanggal,
            'alphaTanggal' => $alphaTanggal,
            'kelasList' => $kelasList,
            'belumTerdaftar' => $belumTerdaftar,
        ])->layout('components.layouts.app');
    }
}
