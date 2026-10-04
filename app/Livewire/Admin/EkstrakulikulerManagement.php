<?php

namespace App\Livewire\Admin;

use App\Models\Ekstrakulikuler;
use App\Models\Guru;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class EkstrakulikulerManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $guruSearch = '';
    public $siswaSearch = '';

    public $ekstrakulikulerId;
    public $nama = '';
    public $deskripsi = '';
    public $guruId;
    public $hari = '';
    public $jamMulai = '';
    public $jamSelesai = '';
    public $lokasi = '';
    public $tahunAjaran = '';
    public $semester = '';
    public $aktif = true;

    public $isEdit = false;

    /** @var array<int, int> */
    public $terpilih = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'guruSearch' => ['except' => ''],
        'siswaSearch' => ['except' => ''],
    ];

    protected $rules = [
        'nama' => ['required', 'string', 'max:191'],
        'deskripsi' => ['nullable', 'string'],
        'guruId' => ['nullable', 'exists:guru,id'],
        'hari' => ['nullable', 'string', 'max:191'],
        'jamMulai' => ['nullable', 'date_format:H:i'],
        'jamSelesai' => ['nullable', 'date_format:H:i', 'after_or_equal:jamMulai'],
        'lokasi' => ['nullable', 'string', 'max:191'],
        'tahunAjaran' => ['nullable', 'string', 'max:20'],
        'semester' => ['nullable', 'string', 'max:20'],
        'aktif' => ['boolean'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGuruSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSiswaSearch(): void
    {
        $this->resetPage('siswaPage');
    }

    public function store()
    {
        $this->validate([
            ...$this->rules,
            'nama' => [...$this->rules['nama'], Rule::unique('ekstrakulikuler', 'nama')],
        ]);

        Ekstrakulikuler::create($this->payload());

        session()->flash('message', 'Ekstrakulikuler "'.trim($this->nama).'" ditambahkan.');

        $this->reset();
    }

    public function edit($id)
    {
        $item = Ekstrakulikuler::findOrFail($id);

        $this->ekstrakulikulerId = $item->id;
        $this->nama = $item->nama;
        $this->deskripsi = $item->deskripsi;
        $this->guruId = $item->guru_id;
        $this->hari = $item->hari;
        $this->jamMulai = $item->jam_mulai ? substr((string) $item->jam_mulai, 0, 5) : '';
        $this->jamSelesai = $item->jam_selesai ? substr((string) $item->jam_selesai, 0, 5) : '';
        $this->lokasi = $item->lokasi;
        $this->tahunAjaran = $item->tahun_ajaran;
        $this->semester = $item->semester;
        $this->aktif = (bool) $item->aktif;
        $this->isEdit = true;

        $this->resetValidation();
    }

    public function update()
    {
        $this->validate([
            ...$this->rules,
            'nama' => [
                ...$this->rules['nama'],
                Rule::unique('ekstrakulikuler', 'nama')->ignore($this->ekstrakulikulerId),
            ],
        ]);

        $item = Ekstrakulikuler::findOrFail($this->ekstrakulikulerId);
        $item->update($this->payload());

        session()->flash('message', 'Ekstrakulikuler "'.$item->nama.'" diperbarui.');

        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->reset([
            'ekstrakulikulerId', 'nama', 'deskripsi', 'guruId', 'hari',
            'jamMulai', 'jamSelesai', 'lokasi', 'tahunAjaran', 'semester',
            'aktif', 'isEdit',
        ]);
        $this->resetValidation();
    }

    public function delete($id)
    {
        $item = Ekstrakulikuler::withCount('siswa')->findOrFail($id);
        $jumlah = $item->siswa_count;

        $item->delete();

        if ($this->ekstrakulikulerId === $id) {
            $this->cancelEdit();
        }

        session()->flash(
            'message',
            $jumlah > 0
                ? "Ekstrakulikuler dihapus beserta {$jumlah} peserta."
                : 'Ekstrakulikuler dihapus.'
        );
    }

    public function toggleAktif($id)
    {
        $item = Ekstrakulikuler::findOrFail($id);
        $item->aktif = ! $item->aktif;
        $item->save();

        session()->flash('message', $item->nama.($item->aktif ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    /**
     * Peserta yang sudah terdaftar pada ekstrakulikuler terpilih.
     */
    public function tambahPeserta()
    {
        $validated = $this->validate([
            'terpilih' => ['required', 'array', 'min:1'],
            'terpilih.*' => ['exists:siswa,id'],
        ]);

        $item = Ekstrakulikuler::findOrFail($this->ekstrakulikulerId);

        $sudah = $item->siswa()->pluck('siswa.id')->all();

        $tambah = array_values(array_diff($validated['terpilih'], $sudah));

        foreach ($tambah as $siswaId) {
            $item->siswa()->attach($siswaId, [
                'tahun_ajaran' => $this->tahunAjaran ?: null,
                'semester' => $this->semester ?: null,
            ]);
        }

        $this->terpilih = [];

        session()->flash('message', count($tambah).' siswa ditambahkan sebagai peserta.');
    }

    public function lepasPeserta($siswaId)
    {
        $item = Ekstrakulikuler::findOrFail($this->ekstrakulikulerId);
        $item->siswa()->detach($siswaId);

        session()->flash('message', 'Siswa dilepas dari peserta.');
    }

    private function payload(): array
    {
        return [
            'nama' => trim($this->nama),
            'deskripsi' => $this->deskripsi ?: null,
            'guru_id' => $this->guruId ?: null,
            'hari' => $this->hari ?: null,
            'jam_mulai' => $this->jamMulai ?: null,
            'jam_selesai' => $this->jamSelesai ?: null,
            'lokasi' => $this->lokasi ?: null,
            'tahun_ajaran' => $this->tahunAjaran ?: null,
            'semester' => $this->semester ?: null,
            'aktif' => (bool) $this->aktif,
        ];
    }

    public function render()
    {
        $search = trim((string) $this->search);
        $like = $search === '' ? null : '%'.addcslashes($search, '%_\\').'%';

        return view('livewire.admin.ekstrakulikuler-management', [
            'daftar' => Ekstrakulikuler::with('guru')
                ->withCount('siswa')
                ->when($like !== null, fn ($q) => $q->where('nama', 'like', $like))
                ->orderBy('nama')
                ->paginate(15),

            'guruList' => Guru::orderBy('nama')
                ->when(trim((string) $this->guruSearch) !== '', function ($q) {
                    $g = '%'.addcslashes(trim((string) $this->guruSearch), '%_\\').'%';
                    $q->where('nama', 'like', $g);
                })
                ->limit(200)
                ->get(),

            'siswaList' => $this->renderSiswaList(),
            'peserta' => $this->renderPeserta(),
        ])->layout('components.layouts.app');
    }

    private function renderSiswaList()
    {
        $search = trim((string) $this->siswaSearch);
        $like = $search === '' ? null : '%'.addcslashes($search, '%_\\').'%';

        $query = Siswa::orderBy('nama');

        if ($this->ekstrakulikulerId !== null) {
            $terdaftar = DB::table('ekstrakulikuler_siswa')
                ->where('ekstrakulikuler_id', $this->ekstrakulikulerId)
                ->pluck('siswa_id')
                ->all();

            $query->whereNotIn('id', $terdaftar ?: [0]);
        }

        return $query
            ->when($like !== null, fn ($q) => $q->where('nama', 'like', $like))
            ->limit(50)
            ->get(['id', 'nama', 'nis']);
    }

    private function renderPeserta()
    {
        if ($this->ekstrakulikulerId === null) {
            return null;
        }

        return Ekstrakulikuler::find($this->ekstrakulikulerId)
            ?->siswa()
            ->orderBy('nama')
            ->get(['siswa.id', 'siswa.nama', 'siswa.nis', 'ekstrakulikuler_siswa.tahun_ajaran', 'ekstrakulikuler_siswa.semester']);
    }
}