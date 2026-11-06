<?php

namespace App\Livewire\Admin;

use App\Models\Device;
use App\Models\User;
use App\Support\Absensi\AbsensiRekap;
use App\Support\Absensi\RfidBelumTerdaftar as RfidService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Halaman kerja untuk tap mesin yang belum punya pemilik.
 *
 * Presensi Guru/Murid hanya menampilkan tap asing per tanggal. Di halaman ini
 * admin melihat seluruh RFID asing sekaligus lalu menghubungkannya ke satu
 * siswa/guru. Setelah dihubungkan, tap berikutnya (dan tap lama pada tanggal
 * yang dibuka) otomatis ikut dikenali oleh AbsensiRekap.
 */
class RfidBelumTerdaftar extends Component
{
    /** Pencarian RFID. */
    public $search = '';

    /** Rentang: '7', '30', '90', atau 'all'. */
    public $rentang = 'all';

    /** Filter nomor seri mesin; kosong = semua mesin. */
    public $mesinFilter = '';

    /** RFID yang sedang dihubungkan; non-kosong berarti modal terbuka. */
    public $assigningRfid = '';

    /** Pencarian pengguna di dalam modal. */
    public $userSearch = '';

    /** Id user yang dipilih di modal. */
    public $selectedUserId = null;

    /** Sembunyikan pengguna yang sudah punya ID mesin. */
    public $hanyaTanpaId = true;

    public function updatingRentang(): void
    {
        $this->resetErrorBag();
    }

    public function updatingSearch(): void
    {
        $this->resetErrorBag();
    }

    public function updatingMesinFilter(): void
    {
        $this->resetErrorBag();
    }

    public function bukaAssign(string $rfid): void
    {
        $this->assigningRfid = trim($rfid);
        $this->userSearch = '';
        $this->selectedUserId = null;
        $this->hanyaTanpaId = true;
        $this->resetErrorBag();
    }

    public function tutupAssign(): void
    {
        $this->assigningRfid = '';
        $this->userSearch = '';
        $this->selectedUserId = null;
        $this->resetErrorBag();
    }

    /**
     * Hubungkan RFID yang sedang dipilih ke siswa/guru.
     *
     * ID mesin disimpan di kolom wajah_id_zkteco karena kolom itulah yang
     * dicocokkan AbsensiRekap. Kolom rfid_kartu hanya diisi bila masih kosong,
     * supaya data lama tidak tertimpa.
     */
    public function hubungkan(): void
    {
        $this->validate([
            'assigningRfid' => ['required', 'string', 'max:32'],
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
        ], [
            'assigningRfid.required' => 'RFID yang akan dihubungkan tidak terbaca. Tutup lalu buka ulang.',
            'selectedUserId.required' => 'Pilih siswa/guru yang memiliki RFID ini.',
            'selectedUserId.exists' => 'Pengguna yang dipilih sudah tidak ada. Muat ulang halaman.',
        ]);

        $rfid = trim((string) $this->assigningRfid);

        // Bisa saja RFID ini baru saja dihubungkan dari tab lain.
        if (isset((new AbsensiRekap)->semuaIdDikenal()[$rfid])) {
            $this->addError('assigningRfid', 'RFID '.$rfid.' sudah terhubung ke pengguna lain. Muat ulang halaman ini.');

            return;
        }

        $user = User::with(['siswa', 'guru'])->find((int) $this->selectedUserId);
        $orang = $user?->siswa ?? $user?->guru;

        if ($user === null || $orang === null) {
            $this->addError('selectedUserId', 'Pengguna ini tidak punya data siswa/guru, jadi tidak bisa diberi ID mesin.');

            return;
        }

        if (filled($orang->wajah_id_zkteco)) {
            $this->addError(
                'selectedUserId',
                'Pengguna ini sudah punya ID mesin ('.$orang->wajah_id_zkteco.'). Hapus dulu ID lamanya di Data Pengguna bila memang ingin diganti.'
            );

            return;
        }

        DB::transaction(function () use ($orang, $rfid): void {
            $orang->wajah_id_zkteco = $rfid;

            if (blank($orang->rfid_kartu)) {
                $orang->rfid_kartu = $rfid;
            }

            $orang->save();
        });

        session()->flash(
            'message',
            'RFID '.$rfid.' berhasil dihubungkan ke '.$user->name.'. Tap berikutnya otomatis masuk rekap presensi.'
        );

        $this->tutupAssign();
    }

    public function render()
    {
        $taps = (new RfidService)->ringkasan($this->hari());

        if (trim((string) $this->search) !== '') {
            $cari = mb_strtolower(trim((string) $this->search));
            $taps = array_values(array_filter(
                $taps,
                fn (array $tap) => str_contains(mb_strtolower($tap['employee_id']), $cari)
            ));
        }

        if ((string) $this->mesinFilter !== '') {
            $taps = array_values(array_filter(
                $taps,
                fn (array $tap) => in_array(
                    (string) $this->mesinFilter,
                    array_column($tap['mesin'], 'sn'),
                    true
                )
            ));
        }

        return view('livewire.admin.rfid-belum-terdaftar', [
            'taps' => $taps,
            'totalTap' => array_sum(array_column($taps, 'jumlahTap')),
            'pengguna' => $this->assigningRfid !== '' ? $this->pengguna() : collect(),
            'devices' => Device::orderBy('nama')->get(['nama', 'no_sn']),
        ])->layout('components.layouts.app');
    }

    private function hari(): ?int
    {
        return match ((string) $this->rentang) {
            '7' => 7,
            '30' => 30,
            '90' => 90,
            default => null,
        };
    }

    /**
     * Kandidat pemilik RFID: siswa/guru, diutamakan yang belum punya ID mesin.
     */
    private function pengguna()
    {
        $search = trim((string) $this->userSearch);
        $like = $search === '' ? null : '%'.addcslashes($search, '%_\\').'%';

        return User::with(['siswa.kelas', 'guru'])
            ->whereIn('role', ['siswa', 'guru'])
            ->when($this->hanyaTanpaId, function ($query) {
                $query->where(function ($outer) {
                    $outer->whereHas('siswa', fn ($siswa) => $siswa->where(function ($id) {
                        $id->whereNull('wajah_id_zkteco')->orWhere('wajah_id_zkteco', '');
                    }))->orWhereHas('guru', fn ($guru) => $guru->where(function ($id) {
                        $id->whereNull('wajah_id_zkteco')->orWhere('wajah_id_zkteco', '');
                    }));
                });
            })
            ->when($like !== null, function ($query) use ($like) {
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('siswa', fn ($siswa) => $siswa->where('nis', 'like', $like))
                        ->orWhereHas('guru', fn ($guru) => $guru->where('nip', 'like', $like));
                });
            })
            ->orderBy('name')
            ->limit(50) // Limit ketat supaya modal tidak overflow
            ->get();
    }
}
