<?php

namespace App\Livewire\Guru;

use App\Models\Kelas;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AbsensiKelasDetail extends Component
{
    public $kelas_id;
    public $kelasList;
    public $tanggal;

    public function mount()
    {
        $guruId = Auth::user()->guru->id ?? null;
        $this->kelasList = Kelas::where('wali_kelas_id', $guruId)->get();
        if ($this->kelasList->isEmpty()) {
            $this->kelasList = Kelas::all();
        }
        if ($this->kelasList->isNotEmpty()) {
            $this->kelas_id = $this->kelasList->first()->id;
        }
        $this->tanggal = date('Y-m-d');
    }

    public function render()
    {
        $siswaList = collect();
        $absensiBySiswa = collect();

        if ($this->kelas_id) {
            $siswaList = Siswa::with('kelas', 'user')
                ->where('kelas_id', $this->kelas_id)
                ->orderBy('nama')
                ->get();

            foreach ($siswaList as $siswa) {
                $attends = $siswa->attendances()
                    ->whereDate('timestamp', $this->tanggal)
                    ->orderBy('timestamp')
                    ->get();

                $masuk = $attends->where('jenis', 'masuk')->first();
                $pulang = $attends->where('jenis', 'pulang')->last();

                $absensiBySiswa->put($siswa->id, [
                    'masuk' => $masuk,
                    'pulang' => $pulang,
                    'total' => $attends->count(),
                ]);
            }
        }

        return view('livewire.guru.absensi-kelas-detail', [
            'siswaList' => $siswaList,
            'absensiBySiswa' => $absensiBySiswa,
        ])->layout('components.layouts.app');
    }
}
