<?php

namespace App\Livewire\Siswa;

use App\Models\Kelas;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $siswa = auth()->user()->siswa;

        // attendance.status1 dipakai mesin ZKTeco sebagai kode hadir; selain 0
        // dihitung sebagai kehadiran. Baris yang tidak ada sama sekali tidak
        // dihitung sebagai hadir maupun tidak hadir agar angka tidak bias.
        $hadir = $siswa?->attendances()->where('status1', '!=', 0)->count() ?? 0;

        return view('livewire.siswa.dashboard', [
            'siswa' => $siswa,
            'kelas' => $siswa?->kelas_id ? Kelas::find($siswa->kelas_id) : null,
            'hadir' => $hadir,
            'total' => $siswa?->attendances()->count() ?? 0,
            'terakhir' => $siswa?->attendances()->latest('timestamp')->limit(5)->get() ?? collect(),
        ])->layout('components.layouts.app', ['title' => 'Dashboard Siswa']);
    }
}