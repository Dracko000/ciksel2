<?php

namespace App\Livewire\Admin;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Guru;
use App\Models\Siswa;
use App\Support\Absensi\PerintahMesin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Component;

/**
 * Halaman khusus untuk mengirim perintah ke mesin ZKTeco.
 *
 * Dipisah dari halaman daftar mesin supaya admin punya tempat tetap untuk
 * melihat antrean, riwayat, dan status perintah. Perintah tetap dikerjakan
 * mesin saat polling, jadi status berubah dari pending -> sent -> done.
 */
class PerintahMesinPage extends Component
{
    /** Device yang dipilih; null berarti belum ada mesin. */
    public $deviceId = null;

    /** Jenis perintah: hapus_user|hapus_semua_user|hapus_log|reboot. */
    public $jenis = 'hapus_user';

    /** PIN/employee_id untuk perintah hapus satu user. */
    public $pin = '';

    /** Ketikan SN mesin sebagai konfirmasi. */
    public $konfirmasi = '';

    /** Pencarian pada daftar pengguna yang pernah tap di mesin. */
    public $cariPengguna = '';

    /** Filter status riwayat: '' semua, pending/sent/done/failed. */
    public $statusFilter = '';

    public $devices;

    public function mount()
    {
        $this->muatDevices();
    }

    public function muatDevices(): void
    {
        $antrean = DeviceCommand::query()
            ->selectRaw('no_sn, count(*) as jumlah')
            ->where('status', 'pending')
            ->groupBy('no_sn')
            ->pluck('jumlah', 'no_sn');

        $this->devices = Device::orderBy('online', 'DESC')->orderBy('nama')->get()
            ->each(fn (Device $d) => $d->antrean = (int) ($antrean[$d->no_sn] ?? 0));

        // Halaman Monitoring Mesin menautkan ke sini dengan ?device=<id>.
        $diminta = (int) request()->query('device', 0);

        if ($diminta && $this->devices->contains('id', $diminta)) {
            $this->deviceId = $diminta;
        } elseif ($this->devices->isNotEmpty() && ! $this->deviceId) {
            $this->deviceId = $this->devices->first()->id;
        }
    }

    public function pilihDevice($id): void
    {
        $this->deviceId = $id;
        $this->pin = '';
        $this->konfirmasi = '';
        $this->cariPengguna = '';
        $this->resetErrorBag();
    }

    public function resetForm(): void
    {
        $this->pin = '';
        $this->konfirmasi = '';
        $this->cariPengguna = '';
        $this->resetErrorBag();
    }

    public function pilihPengguna(string $pin): void
    {
        $this->pin = $pin;
        $this->resetErrorBag('pin');
    }

    public function getDeviceProperty(): ?Device
    {
        return $this->deviceId ? Device::find($this->deviceId) : null;
    }

    /**
     * Daftar PIN yang pernah tap di mesin terpilih, dilengkapi nama pemilik.
     *
     * Mesin tidak bisa ditanya daftar user-nya lewat protokol ini, jadi
     * acuan yang tersedia adalah log tap yang sudah masuk ke server. PIN
     * yang belum pernah tap tetap bisa diketik manual.
     */
    public function getPenggunaMesinProperty(): Collection
    {
        $device = $this->device;

        if (! $device) {
            return collect();
        }

        $rekap = DB::table('attendances')
            ->selectRaw('employee_id, count(*) as jumlah, max(timestamp) as terakhir')
            ->where('sn', $device->no_sn)
            ->whereNotNull('employee_id')
            ->groupBy('employee_id')
            ->orderByDesc('terakhir')
            ->get();

        $ids = $rekap->pluck('employee_id')->all();

        $siswa = Siswa::with('kelas')
            ->where(function ($q) use ($ids) {
                $q->whereIn('wajah_id_zkteco', $ids)->orWhereIn('rfid_kartu', $ids);
            })
            ->get()
            ->keyBy(fn (Siswa $s) => (string) ($s->wajah_id_zkteco ?: $s->rfid_kartu));

        $guru = Guru::where(function ($q) use ($ids) {
            $q->whereIn('wajah_id_zkteco', $ids)->orWhereIn('rfid_kartu', $ids);
        })
            ->get()
            ->keyBy(fn (Guru $g) => (string) ($g->wajah_id_zkteco ?: $g->rfid_kartu));

        $cari = trim(mb_strtolower($this->cariPengguna));

        return $rekap
            ->map(function ($row) use ($siswa, $guru) {
                $pin = (string) $row->employee_id;
                $orang = $siswa->get($pin) ?? $guru->get($pin);

                return [
                    'pin' => $pin,
                    'nama' => $orang->nama ?? null,
                    'tipe' => $orang instanceof Siswa ? 'Siswa' : ($orang ? 'Guru/Tendik' : null),
                    'kelas' => $orang instanceof Siswa ? $orang->kelas?->nama_kelas : null,
                    'jumlah' => (int) $row->jumlah,
                    'terakhir' => $row->terakhir,
                ];
            })
            ->filter(function (array $row) use ($cari) {
                if ($cari === '') {
                    return true;
                }

                return str_contains(mb_strtolower($row['nama'] ?? ''), $cari)
                    || str_contains($row['pin'], $cari);
            })
            ->take(50)
            ->values();
    }

    /**
     * Riwayat perintah, terbaru di atas.
     */
    public function getRiwayatProperty(): Collection
    {
        $device = $this->device;

        if (! $device) {
            return collect();
        }

        return DeviceCommand::untukMesin($device->no_sn)
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->limit(25)
            ->get();
    }

    public function kirim(): void
    {
        $device = $this->device;

        if (! $device) {
            $this->addError('jenis', 'Pilih mesin terlebih dahulu.');

            return;
        }

        // Semua perintah di halaman ini menghapus/merestart mesin, jadi wajib
        // dikonfirmasi dengan mengetik SN. Ini pengaman terakhir sebelum
        // perintah benar-benar masuk antrean.
        if (trim((string) $this->konfirmasi) !== $device->no_sn) {
            $this->addError('konfirmasi', 'Ketik SN mesin ('.$device->no_sn.') untuk mengonfirmasi.');

            return;
        }

        try {
            match ($this->jenis) {
                'hapus_user' => PerintahMesin::hapusUser($device, (string) $this->pin),
                'hapus_semua_user' => PerintahMesin::hapusSemuaUser($device),
                'hapus_log' => PerintahMesin::hapusLog($device),
                'reboot' => PerintahMesin::reboot($device),
                default => throw new InvalidArgumentException('Jenis perintah tidak dikenal.'),
            };
        } catch (InvalidArgumentException $e) {
            $this->addError('jenis', $e->getMessage());

            return;
        }

        $this->pin = '';
        $this->konfirmasi = '';
        $this->muatDevices();

        session()->flash('message', 'Perintah masuk antrean. Mesin menjalankannya pada polling berikutnya.');
    }

    public function render()
    {
        return view('livewire.admin.perintah-mesin')
            ->layout('components.layouts.app', ['title' => 'Perintah Mesin']);
    }
}
