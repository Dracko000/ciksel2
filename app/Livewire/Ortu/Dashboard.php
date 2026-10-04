<?php

namespace App\Livewire\Ortu;

use Livewire\Component;
use App\Models\Siswa;
use App\Models\InformasiSekolah;
use App\Models\JadwalPelajaran;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public function render()
    {
        $userId = Auth::id();
        $anak = Siswa::with('kelas')->where('ortu_user_id', $userId)->get();
        $informasi = InformasiSekolah::latest()->take(5)->get();

        return view('livewire.ortu.dashboard', [
            'anak' => $anak,
            'informasi' => $informasi
        ])->layout('components.layouts.app');
    }
}
