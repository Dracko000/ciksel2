<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Siswa;
use App\Models\Guru;

class AbsensiMonitor extends Component
{
    use WithPagination;

    public $dateFilter;

    public function mount()
    {
        $this->dateFilter = date('Y-m-d');
    }

    public function render()
    {
        $attendances = DB::table('attendances')
            ->whereDate('timestamp', $this->dateFilter)
            ->orderBy('timestamp', 'DESC')
            ->paginate(20);

        // Map employee_id to Siswa/Guru Name
        $employeeIds = $attendances->pluck('employee_id')->unique()->toArray();
        
        $siswas = Siswa::whereIn('wajah_id_zkteco', $employeeIds)->get()->keyBy('wajah_id_zkteco');
        $gurus = Guru::whereIn('wajah_id_zkteco', $employeeIds)->get()->keyBy('wajah_id_zkteco');

        return view('livewire.admin.absensi-monitor', [
            'attendances' => $attendances,
            'siswas' => $siswas,
            'gurus' => $gurus,
        ])->layout('components.layouts.app');
    }
}
