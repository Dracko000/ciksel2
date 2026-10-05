<?php

namespace App\Livewire\Admin;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $kelasList;
    public $name, $email, $username, $password, $role = 'siswa';

    public $search = '';
    public $roleFilter = '';
    public $kelasFilter = '';
    public $perPage = 50;

    /** @var array<int> id user yang dicentang pada tabel. */
    public $selected = [];

    /** Mode konfirmasi hapus: 'single' atau 'bulk'. */
    public $deleteMode = null;

    /** Nama entitas yang harus diketik ulang oleh admin (konfirmasi hapus). */
    public $deleteTargetName = '';

    /** Id user yang menunggu konfirmasi hapus. */
    public $deleteId = null;

    /** Teks yang diketik admin; harus sama persis dengan deleteTargetName. */
    public $deleteConfirmText = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'kelasFilter' => ['except' => ''],
    ];

    // Spesifik Siswa
    public $nis, $kelas_id;
    // Spesifik Guru
    public $nip, $is_tendik = false;

    // Spesifik ZKTeco
    public $rfid_kartu, $wajah_id_zkteco;

    public $isEdit = false;
    public $edit_id;

    public function mount()
    {
        $this->kelasList = Kelas::orderBy('nama_kelas')->get();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingKelasFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    /**
     * Kosongkan pencarian dan semua filter sekaligus.
     */
    public function resetFilter(): void
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->kelasFilter = '';
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingPage(): void
    {
        // Pilihan tidak boleh ikut ke halaman lain:user yang tidak terlihat
        // tidak boleh ikut terhapus.
        $this->selected = [];
    }

    /**
     * Checkbox "pilih semua" hanya berlaku untuk baris yang sedang tampil,
     * supaya admin tidak ohneh menghapus 790 akun tanpa sadar.
     */
    /**
     * Id user yang sedang tampil di halaman ini saja.
     *
     * Dipakai untuk "pilih semua": pada daftar 790 siswa, checkbox header
     * hanya boleh menandai baris yang terlihat. Menandai seluruh hasil
     * query diam-diam adalah bom untuk penghapusan massal.
     */
    protected function visibleIds(): array
    {
        $items = $this->query()->paginate($this->perPage)->items();

        return array_map(static fn ($user) => (int) $user->id, $items);
    }

    public function updatedSelected($value): void
    {
        $visible = $this->visibleIds();

        $this->selected = array_values(array_intersect(
            array_map('intval', (array) $this->selected),
            $visible
        ));
    }

    public function toggleSelectAll(): void
    {
        $visible = $this->visibleIds();

        $this->selected = count($this->selected) === count($visible)
            ? []
            : $visible;
    }

    public function isSelected($id): bool
    {
        return in_array($id, $this->selected, true);
    }

    public function allVisibleSelected(int $visibleCount): bool
    {
        return $visibleCount > 0 && count($this->selected) === $visibleCount;
    }

    /* ------------------------------------------------------------------
     |  Konfirmasi hapus
     |  Hapus tidak pernah dieksekusi langsung dari klik tombol. Modal
     |  memaksa admin mengetik ulang nama entitas (atau HAPUS untuk massal)
     |  supaya salah pilih pada daftar 790 baris tidak fatal.
     | ------------------------------------------------------------------ */

    public function askDelete($id): void
    {
        $user = User::with('siswa', 'guru')->findOrFail($id);

        $this->deleteMode = 'single';
        $this->deleteId = $user->id;
        $this->deleteTargetName = $user->name ?? ('user-' . $user->id);
        $this->deleteConfirmText = '';
        $this->resetErrorBag();
    }

    public function askBulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        $this->deleteMode = 'bulk';
        $this->deleteId = null;
        $this->deleteTargetName = 'HAPUS';
        $this->deleteConfirmText = '';
        $this->resetErrorBag();
    }

    public function cancelDelete(): void
    {
        $this->deleteMode = null;
        $this->deleteId = null;
        $this->deleteTargetName = '';
        $this->deleteConfirmText = '';
        $this->resetErrorBag();
    }

    public function confirmDelete(): void
    {
        $typed = trim((string) $this->deleteConfirmText);
        $expected = trim((string) $this->deleteTargetName);

        if ($typed === '' || strcasecmp($typed, $expected) !== 0) {
            $this->addError('deleteConfirmText', "Ketik persis \"" . $expected . "\" untuk melanjutkan.");

            return;
        }

        // Admin tidak boleh menghapus akunnya sendiri lewat layar ini.
        $ids = $this->deleteMode === 'bulk'
            ? $this->selected
            : [$this->deleteId];

        $ids = array_values(array_filter(
            $ids,
            fn ($id) => (int) $id !== (int) auth()->id()
        ));

        $deleted = User::whereIn('id', $ids)->delete();

        $this->cancelDelete();
        $this->selected = [];

        session()->flash(
            'message',
            $deleted . ' akun berhasil dihapus.'
        );
    }

    /**
     * Daftar user tidak dimuat semua sekaligus: sekolah punya ratusan akun dan
     * memuat semuanya akan ikut terkirim ke browser pada setiap permintaan.
     */
    protected function query()
    {
        $search = trim((string) $this->search);
        $like = $search === '' ? null : '%'.addcslashes($search, '%_\\').'%';

        return User::with(['siswa.kelas', 'guru'])
            ->when($like !== null, function ($query) use ($like) {
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('siswa', fn ($s) => $s->where('nis', 'like', $like))
                        ->orWhereHas('guru', fn ($g) => $g->where('nip', 'like', $like));
                });
            })
            ->when($this->roleFilter !== '', fn ($query) => $query->where('role', $this->roleFilter))
            // Kelas hanya dimiliki siswa; guru dan admin tidak punya kelas_id
            // sehingga otomatis tersaring keluar saat kelas dipilih.
            ->when(
                $this->kelasFilter !== '',
                fn ($query) => $query->whereHas(
                    'siswa',
                    fn ($s) => $s->where('kelas_id', $this->kelasFilter)
                )
            )
            ->orderBy('name');
    }

    public function resetFields()
    {
        $this->name = ''; $this->email = ''; $this->username = ''; $this->password = '';
        $this->role = 'siswa';
        $this->nis = ''; $this->kelas_id = null;
        $this->nip = ''; $this->is_tendik = false;
        $this->rfid_kartu = ''; $this->wajah_id_zkteco = '';
        $this->isEdit = false;
        $this->edit_id = null;
    }

    public function store()
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . ($this->edit_id ?? 'NULL'),
            'role' => 'required',
        ];

        if ($this->role === 'siswa') {
            $rules['nis'] = 'required|unique:siswa,nis';
        } elseif ($this->role === 'guru') {
            $rules['nip'] = 'required|unique:guru,nip';
        } else {
            $rules['username'] = 'required|unique:users,username,' . ($this->edit_id ?? 'NULL');
        }

        $rules['password'] = $this->isEdit ? 'nullable|min:6' : 'nullable|min:6';

        $this->validate($rules);

        $username = $this->deriveUsername();
        $password = $this->password ?: $username;

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $username,
            'role' => $this->role,
        ];
        if ($password) {
            $userData['password'] = Hash::make($password);
        }

        $user = User::updateOrCreate(['id' => $this->edit_id], $userData);

        if ($this->role === 'siswa') {
            Siswa::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nis' => $this->nis,
                    'nama' => $this->name,
                    'kelas_id' => $this->kelas_id,
                    'rfid_kartu' => $this->rfid_kartu,
                    'wajah_id_zkteco' => $this->wajah_id_zkteco,
                    'pin' => $this->nis,
                ]
            );
        } elseif ($this->role === 'guru') {
            Guru::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nip' => $this->nip,
                    'nama' => $this->name,
                    'is_tendik' => $this->is_tendik,
                    'rfid_kartu' => $this->rfid_kartu,
                    'wajah_id_zkteco' => $this->wajah_id_zkteco,
                    'pin' => $this->nip,
                ]
            );
        }

        session()->flash('message', ($this->isEdit ? 'User Updated.' : 'User Created.')
            . ' Username: ' . $username . ($this->password ? '' : ' (password = username)'));
        $this->resetFields();
    }

    public function deriveUsername(): ?string
    {
        if ($this->role === 'siswa') {
            $source = $this->nis;
        } elseif ($this->role === 'guru') {
            $source = $this->nip;
        } else {
            $source = $this->username;
        }

        $source = is_string($source) ? trim($source) : '';

        return $source === '' ? null : $source;
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->edit_id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->role = $user->role;
        $this->isEdit = true;

        if ($user->role === 'siswa' && $user->siswa) {
            $this->nis = $user->siswa->nis;
            $this->kelas_id = $user->siswa->kelas_id;
            $this->rfid_kartu = $user->siswa->rfid_kartu;
            $this->wajah_id_zkteco = $user->siswa->wajah_id_zkteco;
        } elseif ($user->role === 'guru' && $user->guru) {
            $this->nip = $user->guru->nip;
            $this->is_tendik = $user->guru->is_tendik;
            $this->rfid_kartu = $user->guru->rfid_kartu;
            $this->wajah_id_zkteco = $user->guru->wajah_id_zkteco;
        }
    }

    public function render()
    {
        return view('livewire.admin.user-management', [
            'users' => $this->query()->paginate($this->perPage),
        ])->layout('components.layouts.app');
    }
}
