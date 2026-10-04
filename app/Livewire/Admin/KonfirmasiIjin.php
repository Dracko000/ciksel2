<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\PengajuanIjin;

class KonfirmasiIjin extends Component
{
    public function approve($id)
    {
        $ijin = PengajuanIjin::findOrFail($id);
        $ijin->update(['status' => 'disetujui']);
        session()->flash('message', 'Pengajuan disetujui.');
    }

    public function reject($id)
    {
        $ijin = PengajuanIjin::findOrFail($id);
        $ijin->update(['status' => 'ditolak']);
        session()->flash('message', 'Pengajuan ditolak.');
    }

    public function render()
    {
        $pengajuan = PengajuanIjin::with('siswa')->latest()->get();

        return view('livewire.admin.konfirmasi-ijin', [
            'pengajuan' => $pengajuan
        ])->layout('components.layouts.app');
    }
}
