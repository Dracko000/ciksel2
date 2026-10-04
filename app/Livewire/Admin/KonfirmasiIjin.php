<?php

namespace App\Livewire\Admin;

use App\Models\PengajuanIjin;
use App\Notifications\IzinDiputuskan;
use App\Support\Notifikasi;
use Livewire\Component;

class KonfirmasiIjin extends Component
{
    public function approve($id)
    {
        $this->putuskan($id, 'disetujui');
    }

    public function reject($id)
    {
        $this->putuskan($id, 'ditolak');
    }

    /**
     * Ubah status pengajuan lalu beri tahu siswa (dan orang tuanya).
     *
     * Pengajuan yang sudah diputuskan tidak diproses lagi supaya notifikasi
     * tidak terkirim berulang.
     */
    private function putuskan($id, string $keputusan): void
    {
        $ijin = PengajuanIjin::with('siswa')->findOrFail($id);

        if ($ijin->status !== 'pending') {
            session()->flash('message', 'Pengajuan ini sudah berkeputusan sebelumnya.');

            return;
        }

        $ijin->update(['status' => $keputusan]);

        Notifikasi::keSiswaDanOrtu($ijin->siswa_id, new IzinDiputuskan($ijin, $keputusan));

        session()->flash(
            'message',
            'Pengajuan '.$ijin->siswa?->nama.' '.$keputusan.'.'
        );
    }

    public function render()
    {
        $pengajuan = PengajuanIjin::with('siswa')->latest()->paginate(20);

        return view('livewire.admin.konfirmasi-ijin', [
            'pengajuan' => $pengajuan,
        ])->layout('components.layouts.app');
    }
}