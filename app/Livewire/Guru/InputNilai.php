<?php

namespace App\Livewire\Guru;

use Livewire\Component;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Nilai;
use Illuminate\Support\Facades\Auth;

class InputNilai extends Component
{
    public $kelas_id;
    public $siswa_id;
    public $mata_pelajaran;
    public $nilai_angka;
    public $catatan;
    public $semester = 'Ganjil';
    public $tahun_ajaran = '2023/2024';

    public $kelasList;
    public $siswaList = [];

    public function mount()
    {
        $guruId = Auth::user()->guru->id ?? null;
        $this->kelasList = Kelas::where('wali_kelas_id', $guruId)->get();
        if ($this->kelasList->isNotEmpty()) {
            $this->kelas_id = $this->kelasList->first()->id;
            $this->updatedKelasId($this->kelas_id);
        }
    }

    public function updatedKelasId($value)
    {
        $this->siswaList = Siswa::where('kelas_id', $value)->get();
        if ($this->siswaList->isNotEmpty()) {
            $this->siswa_id = $this->siswaList->first()->id;
        }
    }

    public function save()
    {
        $this->validate([
            'siswa_id' => 'required',
            'mata_pelajaran' => 'required',
            'nilai_angka' => 'required|numeric|min:0|max:100',
            'semester' => 'required',
            'tahun_ajaran' => 'required',
        ]);

        Nilai::updateOrCreate(
            [
                'siswa_id' => $this->siswa_id,
                'mata_pelajaran' => $this->mata_pelajaran,
                'semester' => $this->semester,
                'tahun_ajaran' => $this->tahun_ajaran
            ],
            [
                'nilai_angka' => $this->nilai_angka,
                'catatan_guru' => $this->catatan
            ]
        );

        session()->flash('message', 'Nilai berhasil disimpan.');
        $this->reset(['nilai_angka', 'catatan']);
    }

    public function render()
    {
        $existingNilai = Nilai::where('siswa_id', $this->siswa_id)
            ->where('semester', $this->semester)
            ->where('tahun_ajaran', $this->tahun_ajaran)
            ->get();

        return view('livewire.guru.input-nilai', [
            'existingNilai' => $existingNilai
        ])->layout('components.layouts.app');
    }
}
