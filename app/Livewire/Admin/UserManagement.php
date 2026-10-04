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
    public $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
    ];

    // Spesifik Siswa
    public $nis, $kelas_id, $ortu_user_id;
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
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Daftar user tidak dimuat semua sekaligus: sekolah punya ratusan akun dan
     * memuat semuanya akan ikut terkirim ke browser pada setiap permintaan.
     */
    protected function query()
    {
        $search = trim((string) $this->search);
        $like = $search === '' ? null : '%'.addcslashes($search, '%_\\').'%';

        return User::with(['siswa', 'guru'])
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
            ->orderBy('name');
    }

    public function resetFields()
    {
        $this->name = ''; $this->email = ''; $this->username = ''; $this->password = '';
        $this->role = 'siswa';
        $this->nis = ''; $this->kelas_id = null; $this->ortu_user_id = null;
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
                    'ortu_user_id' => $this->ortu_user_id,
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
            $this->ortu_user_id = $user->siswa->ortu_user_id;
            $this->rfid_kartu = $user->siswa->rfid_kartu;
            $this->wajah_id_zkteco = $user->siswa->wajah_id_zkteco;
        } elseif ($user->role === 'guru' && $user->guru) {
            $this->nip = $user->guru->nip;
            $this->is_tendik = $user->guru->is_tendik;
            $this->rfid_kartu = $user->guru->rfid_kartu;
            $this->wajah_id_zkteco = $user->guru->wajah_id_zkteco;
        }
    }

    public function delete($id)
    {
        User::find($id)->delete();
        session()->flash('message', 'User Deleted Successfully.');
    }

    public function render()
    {
        return view('livewire.admin.user-management', [
            'users' => $this->query()->paginate($this->perPage),
        ])->layout('components.layouts.app');
    }
}
