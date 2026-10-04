<?php

namespace App\Livewire\Ortu;

use Livewire\Component;
use App\Models\Siswa;
use App\Models\PengajuanIjin;
use Illuminate\Support\Facades\Auth;

class InputIjin extends Component
{
    public $siswa_id;
    public $jenis = 'sakit';
    public $keterangan;
    public $tanggal_mulai;
    public $tanggal_selesai;

    public function mount()
    {
        $this->tanggal_mulai = date('Y-m-d');
        $this->tanggal_selesai = date('Y-m-d');
        
        $anak = Siswa::where('ortu_user_id', Auth::id())->first();
        if ($anak) {
            $this->siswa_id = $anak->id;
        }
    }

    public function submit()
    {
        $this->validate([
            'siswa_id' => 'required',
            'jenis' => 'required',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'required|min:5',
        ]);

        PengajuanIjin::create([
            'siswa_id' => $this->siswa_id,
            'jenis' => $this->jenis,
            'keterangan' => $this->keterangan,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'status' => 'pending',
        ]);

        session()->flash('message', 'Pengajuan ijin berhasil dikirim. Menunggu konfirmasi admin.');
        $this->reset(['keterangan']);
    }

    public function render()
    {
        $anakList = Siswa::where('ortu_user_id', Auth::id())->get();
        $riwayat = PengajuanIjin::whereIn('siswa_id', $anakList->pluck('id'))->latest()->get();

        return view('livewire.ortu.input-ijin', [
            'anakList' => $anakList,
            'riwayat' => $riwayat
        ])->layout('components.layouts.app');
    }
}
