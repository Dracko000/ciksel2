<?php

namespace App\Livewire\Guru;

use Livewire\Component;
use App\Models\JadwalPelajaran;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public function render()
    {
        $guruId = Auth::user()->guru->id ?? null;
        $jadwalHariIni = JadwalPelajaran::with('kelas')
            ->where('guru_id', $guruId)
            // ->where('hari', now()->locale('id')->dayName) // Uncomment jika sudah punya data real
            ->orderBy('jam_mulai')
            ->get();

        return view('livewire.guru.dashboard', [
            'jadwalHariIni' => $jadwalHariIni
        ])->layout('components.layouts.app');
    }
}
