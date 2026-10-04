<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Guru;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public function render()
    {
        $stats = [
            'total_siswa' => Siswa::count(),
            'total_guru' => Guru::count(),
            'total_users' => User::count(),
            'absensi_hari_ini' => DB::table('attendances')->whereDate('timestamp', date('Y-m-d'))->count(),
        ];

        $latestLogs = DB::table('attendances')->orderBy('timestamp', 'DESC')->take(5)->get();

        return view('livewire.admin.dashboard', [
            'stats' => $stats,
            'latestLogs' => $latestLogs
        ])->layout('components.layouts.app');
    }
}
