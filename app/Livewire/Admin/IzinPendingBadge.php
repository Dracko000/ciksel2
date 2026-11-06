<?php

namespace App\Livewire\Admin;

use App\Models\PengajuanIjin;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Angka jumlah pengajuan izin yang masih menunggu konfirmasi admin.
 *
 * Ditampilkan di samping menu "Konfirmasi Izin" pada sidebar. Angka tetap ada
 * sampai pengajuan diputuskan (disetujui/ditolak); selain dihitung ulang tiap
 * menit lewat polling, komponen ini juga menyegarkan diri begitu admin
 * mengonfirmasi lewat halaman Konfirmasi Izin.
 */
class IzinPendingBadge extends Component
{
    public int $jumlah = 0;

    public function mount(): void
    {
        $this->hitung();
    }

    public function hitung(): void
    {
        // Hanya admin yang boleh melihat angka ini; peran lain (bila komponen
        // ini sampai dirender) tidak menampilkan apa pun.
        if (auth()->user()?->role !== 'admin') {
            $this->jumlah = 0;

            return;
        }

        $this->jumlah = PengajuanIjin::where('status', 'pending')->count();
    }

    #[On('izin-dikonfirmasi')]
    public function onIzinDikonfirmasi(): void
    {
        $this->hitung();
    }

    public function render(): View
    {
        return view('livewire.admin.izin-pending-badge');
    }
}
