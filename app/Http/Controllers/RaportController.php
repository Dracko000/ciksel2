<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\Nilai;
use App\Models\Attendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class RaportController extends Controller
{
    public function download($siswa_id)
    {
        $siswa = Siswa::with(['kelas', 'user'])->findOrFail($siswa_id);
        $nilais = Nilai::where('siswa_id', $siswa_id)
            ->where('semester', 'Ganjil') // Default for demo
            ->where('tahun_ajaran', '2023/2024')
            ->get();
            
        $kehadiran = Attendance::where('employee_id', $siswa->wajah_id_zkteco)
            ->whereMonth('timestamp', date('m'))
            ->select(DB::raw('DATE(timestamp) as date'))
            ->distinct()
            ->count();

        $data = [
            'siswa' => $siswa,
            'nilais' => $nilais,
            'kehadiran' => $kehadiran,
            'tanggal' => date('d F Y'),
        ];

        $pdf = Pdf::loadView('pdf.raport', $data);
        
        return $pdf->download("Raport_{$siswa->nama}.pdf");
    }
}
