<?php

namespace App\Livewire\Admin;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Halaman terpisah untuk menambah dan mengubah pengguna.
 *
 * Form tidak lagi menumpang di daftar pengguna supaya tabel tetap fokus
 * pada pencarian, filter, dan aksi massal. Satu komponen menangani dua
 * mode: tambah (tanpa model) dan ubah (dengan model dari route).
 */
class UserForm extends Component
{
    public ?User $user = null;

    public bool $isEdit = false;

    public $kelasList;

    public $name = '';

    public $email = '';

    public $username = '';

    public $password = '';

    public $role = 'siswa';

    // Spesifik siswa
    public $nis = '';

    public $kelas_id = null;

    // Spesifik guru
    public $nip = '';

    public $is_tendik = false;

    // Spesifik ZKTeco
    public $rfid_kartu = '';

    public $wajah_id_zkteco = '';

    /** RFID yang dibawa dari halaman "RFID Belum Terdaftar". */
    public $rfidDariTap = '';

    public function mount(?User $user = null): void
    {
        $this->kelasList = Kelas::orderBy('nama_kelas')->get();

        if ($user && $user->exists) {
            $this->user = $user;
            $this->isEdit = true;
            $this->isiDariUser($user);

            return;
        }

        // Jembatan dari halaman "RFID Belum Terdaftar": ID mesin sudah terisi
        // supaya admin tidak perlu menyalin RFID secara manual.
        $rfid = trim((string) request()->query('rfid', ''));

        if ($rfid !== '') {
            $this->wajah_id_zkteco = $rfid;
            $this->rfidDariTap = $rfid;
        }
    }

    protected function isiDariUser(User $user): void
    {
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->username = $user->username ?? '';
        $this->role = $user->role ?? 'siswa';

        if ($user->role === 'siswa' && $user->siswa) {
            $this->nis = $user->siswa->nis ?? '';
            $this->kelas_id = $user->siswa->kelas_id;
            $this->rfid_kartu = $user->siswa->rfid_kartu ?? '';
            $this->wajah_id_zkteco = $user->siswa->wajah_id_zkteco ?? '';
        } elseif ($user->role === 'guru' && $user->guru) {
            $this->nip = $user->guru->nip ?? '';
            $this->is_tendik = (bool) $user->guru->is_tendik;
            $this->rfid_kartu = $user->guru->rfid_kartu ?? '';
            $this->wajah_id_zkteco = $user->guru->wajah_id_zkteco ?? '';
        }
    }

    public function rules(): array
    {
        $userId = $this->user?->id;

        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => 'required|in:siswa,guru,admin',
            'password' => 'nullable|min:6',
            'wajah_id_zkteco' => 'nullable|string|max:255',
            'rfid_kartu' => 'nullable|string|max:255',
        ];

        if ($this->role === 'siswa') {
            $siswaId = $this->user?->siswa?->id;

            $rules['nis'] = [
                'required',
                Rule::unique('siswa', 'nis')->ignore($siswaId),
            ];
        } elseif ($this->role === 'guru') {
            $guruId = $this->user?->guru?->id;

            $rules['nip'] = [
                'required',
                Rule::unique('guru', 'nip')->ignore($guruId),
            ];
        } else {
            $rules['username'] = [
                'required',
                Rule::unique('users', 'username')->ignore($userId),
            ];
        }

        return $rules;
    }

    public function simpan()
    {
        $this->validate();

        $username = $this->deriveUsername();

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $username,
            'role' => $this->role,
        ];

        // Saat membuat akun, password kosong berarti memakai username. Saat
        // mengubah, password kosong berarti tidak diubah sama sekali.
        if ($this->password !== '') {
            $userData['password'] = Hash::make($this->password);
        } elseif (! $this->isEdit) {
            $userData['password'] = Hash::make((string) $username);
        }

        if ($this->isEdit) {
            $this->user->update($userData);
            $user = $this->user;
        } else {
            $user = User::create($userData);
        }

        if ($this->role === 'siswa') {
            Siswa::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nis' => $this->nis,
                    'nama' => $this->name,
                    'kelas_id' => $this->kelas_id ?: null,
                    'rfid_kartu' => $this->rfid_kartu ?: null,
                    'wajah_id_zkteco' => $this->wajah_id_zkteco ?: null,
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
                    'rfid_kartu' => $this->rfid_kartu ?: null,
                    'wajah_id_zkteco' => $this->wajah_id_zkteco ?: null,
                    'pin' => $this->nip,
                ]
            );
        }

        $pesan = ($this->isEdit ? 'Pengguna diperbarui.' : 'Pengguna dibuat.')
            .' Username: '.$username
            .($this->password === '' && ! $this->isEdit ? ' (password = username)' : '');

        session()->flash('message', $pesan);

        return $this->redirectRoute('admin.users', navigate: true);
    }

    public function batal()
    {
        return $this->redirectRoute('admin.users', navigate: true);
    }

    public function deriveUsername(): ?string
    {
        $source = match ($this->role) {
            'siswa' => $this->nis,
            'guru' => $this->nip,
            default => $this->username,
        };

        $source = is_string($source) ? trim($source) : '';

        return $source === '' ? null : $source;
    }

    public function render()
    {
        return view('livewire.admin.user-form')
            ->layout('components.layouts.app', [
                'title' => $this->isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna',
            ]);
    }
}
