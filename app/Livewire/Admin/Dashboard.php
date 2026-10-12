<?php

namespace App\Livewire\Admin;

use App\Models\Device;
use App\Models\Kelas;
use App\Models\PengajuanIjin;
use App\Models\Siswa;
use App\Support\Absensi\JamKelas;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        // Kehadiran hari ini tidak lagi berupa dua kartu angka, melainkan
        // donut dari $pieData['all'] di bawah: siswa unik yang tap masuk
        // dibanding total siswa. Tap pulang tidak ikut dihitung.
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $stats = [
            'izin_menunggu' => PengajuanIjin::where('status', 'pending')->count(),
            'mesin_online' => Device::where('online', true)->count(),
        ];

        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $siswaByKelas = Siswa::select('kelas_id', DB::raw('count(*) as total'))->groupBy('kelas_id')->pluck('total', 'kelas_id')->toArray();

        // Tap masuk pertama tiap siswa hari ini, dipisah jadi tepat waktu vs
        // terlambat memakai jam kelas masing-masing. Satu siswa dihitung sekali
        // walau tap pulangnya banyak.
        $tapMasukHariIni = DB::table('attendances')
            ->whereBetween('attendances.timestamp', [$todayStart, $todayEnd])
            ->where('attendances.jenis', 'masuk')
            ->join('siswa', 'attendances.employee_id', '=', 'siswa.wajah_id_zkteco')
            ->whereNotNull('siswa.wajah_id_zkteco')
            ->groupBy('attendances.employee_id', 'siswa.kelas_id')
            ->selectRaw('siswa.kelas_id, MIN(attendances.timestamp) as masuk')
            ->get();

        $namaKelasPerId = $kelasList->pluck('nama_kelas', 'id')->all();

        $rekapKelas = [];
        $hadirAll = 0;
        $terlambatAll = 0;

        foreach ($tapMasukHariIni as $tap) {
            $rekapKelas[$tap->kelas_id] ??= ['hadir' => 0, 'terlambat' => 0];

            if (JamKelas::terlambat($tap->masuk, $namaKelasPerId[$tap->kelas_id] ?? null)) {
                $terlambatAll++;
                $rekapKelas[$tap->kelas_id]['terlambat']++;
            } else {
                $hadirAll++;
                $rekapKelas[$tap->kelas_id]['hadir']++;
            }
        }

        $pieData = [];
        $totalSiswaAll = Siswa::count();
        $pieData['all'] = [
            'hadir' => $hadirAll,
            'terlambat' => $terlambatAll,
            'tidak' => max(0, $totalSiswaAll - $hadirAll - $terlambatAll),
            'total' => $totalSiswaAll,
        ];

        foreach ($kelasList as $kelas) {
            $total = $siswaByKelas[$kelas->id] ?? 0;
            $hadir = $rekapKelas[$kelas->id]['hadir'] ?? 0;
            $terlambat = $rekapKelas[$kelas->id]['terlambat'] ?? 0;
            $pieData[$kelas->id] = [
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'tidak' => max(0, $total - $hadir - $terlambat),
                'total' => $total,
            ];
        }

        $latestLogs = DB::table('attendances')->orderBy('timestamp', 'DESC')->take(5)->get();

        return view('livewire.admin.dashboard', [
            'stats' => $stats,
            'latestLogs' => $latestLogs,
            'kelasList' => $kelasList,
            'pieData' => $pieData,
        ])->layout('components.layouts.app');
    }
}
