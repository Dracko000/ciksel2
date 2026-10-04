<?php

namespace App\Livewire\Admin;

use App\Models\Kelas;
use App\Models\PengajuanIjin;
use App\Models\Siswa;
use App\Notifications\IzinDiajukan;
use App\Support\Notifikasi;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Mencatat pengajuan izin atau sakit behalf siswa.
 *
 * Dulu form ini hanya bisa diakses orang tua lewat portal ortu, tetapi role
 * "ortu" tidak pernah ada di enum users.role sehingga halaman tersebut tidak
 * bisa dibuka akun mana pun. Form dipindahkan ke sisi admin supaya pengajuan
 * tetap bisa dibuat dan halaman konfirmasi admin tidak terpakai kosong.
 */
class InputIjin extends Component
{
    use WithPagination;

    #[Url(as: 'siswa', except: '')]
    public $siswa_id = '';

    public $jenis = 'sakit';

    public $keterangan;

    public $tanggal_mulai;

    public $tanggal_selesai;

    public $cariSiswa = '';

    public function mount(): void
    {
        $this->tanggal_mulai = now()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
    }

    public function updatedCariSiswa(): void
    {
        $this->resetPage();
    }

    public function submit()
    {
        $this->validate([
            'siswa_id' => 'required|integer|exists:siswa,id',
            'jenis' => 'required|in:sakit,izin',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'required|string|min:5',
        ]);

        $ijin = PengajuanIjin::create([
            'siswa_id' => $this->siswa_id,
            'jenis' => $this->jenis,
            'keterangan' => $this->keterangan,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'status' => 'pending',
        ]);

        Notifikasi::keAdmin(new IzinDiajukan($ijin->load('siswa')));

        session()->flash(
            'message',
            'Pengajuan '.$this->jenis.' atas nama '
                .Siswa::find($this->siswa_id)?->nama.' berhasil dicatat.'
        );

        $this->reset(['keterangan', 'siswa_id', 'cariSiswa']);
        $this->tanggal_mulai = now()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.admin.input-ijin', [
            'siswaList' => Siswa::query()
                ->when($this->cariSiswa !== '', function ($query) {
                    $query->where('nama', 'like', '%'.$this->cariSiswa.'%')
                        ->orWhere('nis', 'like', '%'.$this->cariSiswa.'%');
                })
                ->orderBy('nama')
                ->limit(50)
                ->get(),
            'kelas' => Kelas::orderBy('nama_kelas')->get(['id', 'nama_kelas']),
            'riwayat' => PengajuanIjin::with('siswa')->latest()->paginate(10),
        ])->layout('components.layouts.app', ['title' => 'Pengajuan Izin']);
    }
}