<?php

namespace App\Livewire\Admin;

use App\Models\Kelas;
use App\Models\PengajuanIjin;
use App\Notifications\IzinDiputuskan;
use App\Support\Notifikasi;
use Livewire\Component;
use Livewire\WithPagination;

class KonfirmasiIjin extends Component
{
    use WithPagination;

    /**
     * Filter kelas pengaju. String kosong berarti semua kelas.
     */
    public $kelasFilter = '';

    public function updatingKelasFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->kelasFilter = '';
        $this->resetPage();
    }
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

        Notifikasi::kePemilikSiswa($ijin->siswa_id, new IzinDiputuskan($ijin, $keputusan));

        session()->flash(
            'message',
            'Pengajuan '.$ijin->siswa?->nama.' '.$keputusan.'.'
        );
    }

    public function render()
    {
        $pengajuan = PengajuanIjin::with('siswa.kelas')
            // Pengajuan izin hanya bisa berasal dari siswa, jadi filter
            // kelas ditelusuri lewat relasi siswa.
            ->when(
                $this->kelasFilter !== '',
                fn ($query) => $query->whereHas(
                    'siswa',
                    fn ($siswa) => $siswa->where('kelas_id', $this->kelasFilter)
                )
            )
            ->latest()
            ->paginate(20);

        return view('livewire.admin.konfirmasi-ijin', [
            'pengajuan' => $pengajuan,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(['id', 'nama_kelas']),
        ])->layout('components.layouts.app');
    }
}