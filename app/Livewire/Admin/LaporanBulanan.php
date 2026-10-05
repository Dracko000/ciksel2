<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class LaporanBulanan extends Component
{
    public $month, $year;
    public $reportData = [];

    /**
     * Filter kelas. String kosong berarti semua kelas.
     */
    public $kelasFilter = '';

    public function mount()
    {
        $this->month = date('m');
        $this->year = date('Y');
        $this->generateReport();
    }

    public function generateReport()
    {
        // Filter kelas diterapkan di level query, bukan setelahnya, supaya
        // laporan satu kelas tidak tetap memuat seluruh siswa sekolah.
        $siswas = Siswa::with('kelas')
            ->when($this->kelasFilter !== '', fn ($query) => $query->where('kelas_id', $this->kelasFilter))
            ->get();

        $this->reportData = [];

        foreach ($siswas as $siswa) {
            $hadir = Attendance::where('employee_id', $siswa->wajah_id_zkteco)
                ->whereMonth('timestamp', $this->month)
                ->whereYear('timestamp', $this->year)
                ->select(DB::raw('DATE(timestamp) as date'))
                ->distinct()
                ->count();

            $this->reportData[] = [
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                'hadir' => $hadir
            ];
        }
    }

    /**
     * Bulan, tahun, dan kelas memakai wire:model biasa (deferred), jadi
     * laporan baru dihitung saat tombol "Terapkan" ditekan. Menambahkan
     * hook updating* di sini akan menghitung ulang dua kali.
     */
    public function render()
    {
        return view('livewire.admin.laporan-bulanan', [
            'kelasList' => Kelas::orderBy('nama_kelas')->get(['id', 'nama_kelas']),
        ])->layout('components.layouts.app');
    }
}