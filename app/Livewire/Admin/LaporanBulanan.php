<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Siswa;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class LaporanBulanan extends Component
{
    public $month, $year;
    public $reportData = [];

    public function mount()
    {
        $this->month = date('m');
        $this->year = date('Y');
        $this->generateReport();
    }

    public function generateReport()
    {
        $siswas = Siswa::with('kelas')->get();
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

    public function render()
    {
        return view('livewire.admin.laporan-bulanan')->layout('components.layouts.app');
    }
}
