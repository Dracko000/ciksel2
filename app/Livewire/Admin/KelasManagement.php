<?php

namespace App\Livewire\Admin;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class KelasManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $waliSearch = '';

    public $kelasId;
    public $namaKelas = '';
    public $waliKelasId;

    public $isEdit = false;
    public $showRosterFor;
    public $rosterSearch = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'waliSearch' => ['except' => ''],
        'rosterSearch' => ['except' => ''],
    ];

    protected $rules = [
        'namaKelas' => ['required', 'string', 'max:191'],
        'waliKelasId' => ['nullable', 'exists:guru,id'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingWaliSearch(): void
    {
        $this->resetPage();
    }

    public function store()
    {
        $this->validate([
            ...$this->rules,
            'namaKelas' => [...$this->rules['namaKelas'], Rule::unique('kelas', 'nama_kelas')],
        ]);

        Kelas::create([
            'nama_kelas' => trim($this->namaKelas),
            'wali_kelas_id' => $this->waliKelasId ?: null,
        ]);

        session()->flash('message', 'Kelas "'.trim($this->namaKelas).'" ditambahkan.');

        $this->reset();
    }

    public function edit($id)
    {
        $kelas = Kelas::findOrFail($id);

        $this->kelasId = $kelas->id;
        $this->namaKelas = $kelas->nama_kelas;
        $this->waliKelasId = $kelas->wali_kelas_id;
        $this->isEdit = true;

        $this->resetValidation();
    }

    public function update()
    {
        $this->validate([
            ...$this->rules,
            'namaKelas' => [
                ...$this->rules['namaKelas'],
                Rule::unique('kelas', 'nama_kelas')->ignore($this->kelasId),
            ],
        ]);

        $kelas = Kelas::findOrFail($this->kelasId);
        $kelas->update([
            'nama_kelas' => trim($this->namaKelas),
            'wali_kelas_id' => $this->waliKelasId ?: null,
        ]);

        session()->flash('message', 'Kelas "'.$kelas->nama_kelas.'" diperbarui.');

        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->reset(['kelasId', 'namaKelas', 'waliKelasId', 'isEdit']);
        $this->resetValidation();
    }

    /** Id kelas yang menunggu konfirmasi hapus. */
    public $hapusKelasId = null;

    /** Nama kelas yang harus diketik ulang admin. */
    public $hapusKelasNama = '';

    /** Teks yang diketik admin; harus sama persis dengan hapusKelasNama. */
    public $hapusKelasKonfirmasi = '';

    /** Jumlah siswa yang ikut kehilangan kelas (untuk pesan dampak). */
    public $hapusKelasJumlahSiswa = 0;

    /**
     * Hapus kelas. Siswa di dalamnya tidak ikut terhapus, kelas_id-nya jadi null
     * karena kolom itu nullable dengan onDelete set null.
     *
     * Sengaja tidak bisa dipanggil langsung: view memakai askDelete() lalu
     * confirmHapusKelas() supaya admin mengetik nama kelas lebih dulu.
     */
    public function askDelete($id)
    {
        $kelas = Kelas::withCount('siswa')->findOrFail($id);

        $this->hapusKelasId = $kelas->id;
        $this->hapusKelasNama = $kelas->nama_kelas;
        $this->hapusKelasJumlahSiswa = $kelas->siswa_count;
        $this->hapusKelasKonfirmasi = '';
        $this->resetValidation();
    }

    public function cancelHapusKelas(): void
    {
        $this->reset(['hapusKelasId', 'hapusKelasNama', 'hapusKelasKonfirmasi', 'hapusKelasJumlahSiswa']);
        $this->resetValidation();
    }

    public function confirmHapusKelas(): void
    {
        $typed = trim((string) $this->hapusKelasKonfirmasi);
        $expected = trim((string) $this->hapusKelasNama);

        if ($typed === '' || strcasecmp($typed, $expected) !== 0) {
            $this->addError('hapusKelasKonfirmasi', 'Ketik persis "' . $expected . '" untuk melanjutkan.');

            return;
        }

        $kelas = Kelas::withCount('siswa')->findOrFail($this->hapusKelasId);
        $jumlahSiswa = $kelas->siswa_count;

        $kelas->delete();

        if ($this->showRosterFor === $kelas->id) {
            $this->showRosterFor = null;
        }

        $this->cancelHapusKelas();

        session()->flash(
            'message',
            $jumlahSiswa > 0
                ? "Kelas dihapus. {$jumlahSiswa} siswa kini belum punya kelas dan perlu dipindahkan."
                : 'Kelas dihapus.'
        );
    }

    public function showRoster($id)
    {
        $this->showRosterFor = $this->showRosterFor === $id ? null : $id;
        $this->rosterSearch = '';
        $this->resetPage();
    }

    /**
     * Pindahkan siswa ke kelas lain tanpa membuka form edit user.
     */
    public function pindahSiswa($siswaId, $kelasId)
    {
        // Nilai ini datang dari parameter method, bukan properti Livewire,
        // jadi harus divalidasi manual.
        $validated = validator(
            ['siswa_id' => $siswaId, 'kelas_id' => $kelasId ?: null],
            [
                'siswa_id' => ['required', 'exists:siswa,id'],
                'kelas_id' => ['nullable', 'exists:kelas,id'],
            ],
            ['kelas_id' => 'kelas tujuan']
        )->validate();

        Siswa::whereKey($validated['siswa_id'])
            ->update(['kelas_id' => $validated['kelas_id']]);

        session()->flash('message', 'Siswa dipindahkan ke kelas yang dipilih.');
    }

    public function render()
    {
        $search = trim((string) $this->search);
        $like = $search === '' ? null : '%'.addcslashes($search, '%_\\').'%';

        return view('livewire.admin.kelas-management', [
            'kelasList' => Kelas::with('waliKelas')
                ->withCount('siswa')
                ->when($like !== null, fn ($q) => $q->where('nama_kelas', 'like', $like))
                ->orderBy('nama_kelas')
                ->paginate(30),

            'waliList' => Guru::orderBy('nama')
                ->when(trim((string) $this->waliSearch) !== '', function ($q) {
                    $waliLike = '%'.addcslashes(trim((string) $this->waliSearch), '%_\\').'%';
                    $q->where('nama', 'like', $waliLike);
                })
                ->limit(200)
                ->get(),

            'kelasOpsi' => Kelas::orderBy('nama_kelas')->pluck('nama_kelas', 'id'),

            'roster' => $this->renderRoster(),
        ])->layout('components.layouts.app');
    }

    /**
     * Daftar siswa pada kelas yang sedang dibuka, dipaginasi terpisah dari daftar kelas.
     */
    private function renderRoster(): ?\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if ($this->showRosterFor === null) {
            return null;
        }

        $search = trim((string) $this->rosterSearch);
        $like = $search === '' ? null : '%'.addcslashes($search, '%_\\').'%';

        return Siswa::with('user')
            ->where('kelas_id', $this->showRosterFor)
            ->when($like !== null, fn ($q) => $q->where('nama', 'like', $like))
            ->orderBy('nama')
            ->paginate(25, ['*'], 'rosterPage');
    }
}