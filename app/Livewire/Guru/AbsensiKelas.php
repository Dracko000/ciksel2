<?php

namespace App\Livewire\Guru;

use Livewire\Component;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AbsensiKelas extends Component
{
    public $kelas_id;
    public $kelasList;
    public $tanggal;

    public function mount()
    {
        // Ambil kelas dimana guru ini menjadi wali kelas, atau semua kelas jika ingin lihat global
        $guruId = Auth::user()->guru->id ?? null;
        $this->kelasList = Kelas::where('wali_kelas_id', $guruId)->get();
        if ($this->kelasList->isNotEmpty()) {
            $this->kelas_id = $this->kelasList->first()->id;
        }
        $this->tanggal = date('Y-m-d');
    }

    public function render()
    {
        $siswaList = collect();
        $attendances = collect();

        if ($this->kelas_id) {
            $siswaList = Siswa::where('kelas_id', $this->kelas_id)->get();
            $zktecoIds = $siswaList->pluck('wajah_id_zkteco')->filter()->toArray();

            if (!empty($zktecoIds)) {
                $attendances = DB::table('attendances')
                    ->whereIn('employee_id', $zktecoIds)
                    ->whereDate('timestamp', $this->tanggal)
                    ->get()
                    ->keyBy('employee_id');
            }
        }

        return view('livewire.guru.absensi-kelas', [
            'siswaList' => $siswaList,
            'attendances' => $attendances
        ])->layout('components.layouts.app');
    }
}
