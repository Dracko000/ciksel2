<?php

namespace App\Livewire\Siswa;

use App\Models\PengajuanIjin;
use App\Notifications\IzinDiajukan;
use App\Support\Notifikasi;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pengajuan izin dan sakit yang diisi lewat akun siswa.
 *
 * Sekolah ini tidak punya akun orang tua terpisah: orang tua memakai akun
 * anaknya, jadi form pengajuan harusnya ada di sisi siswa. Admin hanya
 * meninjau dan memutuskan di halaman konfirmasi.
 */
class InputIjin extends Component
{
    use WithPagination;

    public $jenis = 'sakit';

    public $keterangan;

    public $tanggal_mulai;

    public $tanggal_selesai;

    public function mount(): void
    {
        $this->tanggal_mulai = now()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
    }

    public function submit()
    {
        $siswaId = auth()->user()->siswa?->id;

        $this->validate([
            'jenis' => 'required|in:sakit,izin',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'required|string|min:5',
        ]);

        if (! $siswaId) {
            $this->addError('keterangan', 'Akun ini belum terhubung ke data siswa.');

            return;
        }

        $ijin = PengajuanIjin::create([
            'siswa_id' => $siswaId,
            'jenis' => $this->jenis,
            'keterangan' => $this->keterangan,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'status' => 'pending',
        ]);

        Notifikasi::keAdmin(new IzinDiajukan($ijin->load('siswa')));

        session()->flash('message', 'Pengajuan berhasil dikirim. Menunggu persetujuan sekolah.');

        $this->reset('keterangan');
        $this->tanggal_mulai = now()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $siswaId = auth()->user()->siswa?->id;

        return view('livewire.siswa.input-ijin', [
            'riwayat' => $siswaId
                ? PengajuanIjin::where('siswa_id', $siswaId)->latest()->paginate(10)
                : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
            'punyaDataSiswa' => (bool) $siswaId,
        ])->layout('components.layouts.app', ['title' => 'Pengajuan Izin']);
    }
}