<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InformasiSekolah;
use App\Models\Kelas;
use Illuminate\Support\Facades\Auth;

class InformasiSekolahManagement extends Component
{
    public $informasi;
    public $judul, $konten, $tanggal_publikasi, $tujuan = 'umum', $kelas_id = null;
    public $edit_id;
    public $isEdit = false;

    public function mount()
    {
        $this->loadInformasi();
        $this->tanggal_publikasi = date('Y-m-d');
    }

    public function loadInformasi()
    {
        $this->informasi = InformasiSekolah::with('kelas', 'author')->latest()->get();
    }

    public function resetFields()
    {
        $this->judul = ''; $this->konten = ''; 
        $this->tanggal_publikasi = date('Y-m-d');
        $this->tujuan = 'umum';
        $this->kelas_id = null;
        $this->isEdit = false; $this->edit_id = null;
    }

    public function store()
    {
        $this->validate([
            'judul' => 'required|min:5',
            'konten' => 'required',
            'tanggal_publikasi' => 'required|date',
            'tujuan' => 'required|in:umum,kelas',
            'kelas_id' => 'nullable|exists:kelas,id',
        ]);

        if ($this->tujuan === 'umum') {
            $this->kelas_id = null;
        }

        InformasiSekolah::updateOrCreate(
            ['id' => $this->edit_id],
            [
                'judul' => $this->judul,
                'konten' => $this->konten,
                'tanggal_publikasi' => $this->tanggal_publikasi,
                'tujuan' => $this->tujuan,
                'kelas_id' => $this->kelas_id,
                'author_id' => Auth::id()
            ]
        );

        session()->flash('message', $this->isEdit ? 'Informasi Diperbarui.' : 'Informasi Berhasil Diterbitkan.');
        $this->resetFields();
        $this->loadInformasi();
    }

    public function edit($id)
    {
        $info = InformasiSekolah::findOrFail($id);
        $this->edit_id = $info->id;
        $this->judul = $info->judul;
        $this->konten = $info->konten;
        $this->tanggal_publikasi = $info->tanggal_publikasi;
        $this->tujuan = $info->tujuan ?? 'umum';
        $this->kelas_id = $info->kelas_id;
        $this->isEdit = true;
    }

    public function delete($id)
    {
        InformasiSekolah::find($id)->delete();
        session()->flash('message', 'Informasi Dihapus.');
        $this->loadInformasi();
    }

    public function render()
    {
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        return view('livewire.admin.informasi-sekolah-management', [
            'kelasList' => $kelasList,
        ])->layout('components.layouts.app');
    }
}
