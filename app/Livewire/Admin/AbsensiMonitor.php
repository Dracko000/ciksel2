<?php

namespace App\Livewire\Admin;

use App\Models\Kelas;
use App\Support\Absensi\AbsensiRekap;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Component;
use Livewire\WithPagination;

class AbsensiMonitor extends Component
{
    use WithPagination;

    public $dateFilter;

    /**
     * Filter kelas. String kosong berarti semua kelas.
     */
    public $kelasFilter = '';

    /**
     * Filter peran yang teridentifikasi dari tap. String kosong = semua.
     */
    public $roleFilter = '';

    /** Jumlah baris rekap per halaman. */
    public const PER_PAGE = 20;

    public function mount()
    {
        $this->dateFilter = date('Y-m-d');
    }

    /**
     * Ganti tanggal atau kelas mengembalikan ke halaman 1, supaya filter
     * tidak menyisakan nomor halaman yang sudah tidak ada isinya.
     */
    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatingKelasFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->kelasFilter = '';
        $this->roleFilter = '';
        $this->resetPage();
    }

    public function resetKelasFilter()
    {
        $this->kelasFilter = '';
        $this->resetPage();
    }

    /**
     * Rekap harian: satu baris per orang (bukan satu baris per tap), jadi
     * 192 tap mentah pada satu hari terbaca menjadi daftar nama dengan jam
     * masuk/pulang yang sudah diagregasi. Siswa tampil dikelompokkan per
     * kelas, lalu guru/tendik, lalu tap yang tidak dikenal.
     */
    public function render()
    {
        $rekap = new AbsensiRekap;

        $taps = $rekap->tapHari($this->dateFilter);
        $groups = $rekap->orang(
            (string) $this->kelasFilter,
            (string) $this->roleFilter,
            $taps->keys()->all()
        );

        $rows = $rekap->baris($groups, $taps);
        // Terlambat tetap terhitung hadir; hanya Alpha yang tidak hadir.
        $hadir = collect($rows)->whereIn('status', ['Hadir', 'Terlambat'])->count();
        $terlambat = collect($rows)->where('status', 'Terlambat')->count();

        $page = max(1, (int) $this->getPage('page'));

        // Paginasi dilakukan di memori karena sumbernya sudah berupa baris
        // rekap (puluhan), bukan query log. Pengaturan halaman tetap memakai
        // WithPagination supaya wire:click gotoPage dari view tetap bekerja.
        $attendances = new LengthAwarePaginator(
            collect($rows)->forPage($page, self::PER_PAGE),
            count($rows),
            self::PER_PAGE,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        // Hanya kelas yang siswanya sudah terdaftar di mesin absensi, supaya
        // pilihan filter tidak menghasilkan tabel kosong tanpa alasan.
        $kelasList = Kelas::whereHas('siswa', fn ($query) => $query->whereNotNull('wajah_id_zkteco'))
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas']);

        return view('livewire.admin.absensi-monitor', [
            'attendances' => $attendances,
            'rekap' => $rows,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'alpha' => count($rows) - $hadir,
            'kelasList' => $kelasList,
        ])->layout('components.layouts.app');
    }
}
