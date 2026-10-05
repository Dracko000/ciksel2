<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Siswa;
use App\Models\Device;
use App\Models\PengajuanIjin;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public function render()
    {
        $stats = [
            'total_siswa' => Siswa::count(),
            'absensi_hari_ini' => DB::table('attendances')->whereDate('timestamp', date('Y-m-d'))->count(),
            'izin_menunggu' => PengajuanIjin::where('status', 'pending')->count(),
            'mesin_online' => Device::where('online', true)->count(),
        ];

        $latestLogs = DB::table('attendances')->orderBy('timestamp', 'DESC')->take(5)->get();

        return view('livewire.admin.dashboard', [
            'stats' => $stats,
            'latestLogs' => $latestLogs
        ])->layout('components.layouts.app');
    }
}
