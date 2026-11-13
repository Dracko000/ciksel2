<?php

namespace App\Livewire\Admin;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Support\Absensi\PerintahMesin;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Component;

/**
 * Monitoring mesin ZKTeco sekaligus pintu untuk mengirim perintah ke mesin.
 *
 * Perintah tidak dikirim langsung. Baris masuk ke tabel `device_commands`,
 * lalu mesin mengambilnya sendiri lewat polling `/iclock/getrequest`. Karena
 * itu tombol "Kirim" hanya berarti "masuk antrean".
 */
class DeviceManagement extends Component
{
    public $devices;

    public $edit_id;

    public $no_sn;

    public $nama;

    public $lokasi;

    public $secret_token;

    public $isEdit = false;

    /** Mesin yang sedang dibuka panel perintahnya. */
    public $perintahDeviceId = null;

    /** Jenis perintah: hapus_user|hapus_semua_user|hapus_log|reboot. */
    public $perintahJenis = 'hapus_user';

    /** PIN/employee_id yang dihapus dari mesin. */
    public $perintahPin = '';

    /** Admin wajib mengetik SN mesin sebelum perintah destruktif dikirim. */
    public $konfirmasi = '';

    /** Riwayat perintah mesin terpilih. */
    public $riwayat = [];

    public function mount()
    {
        $this->loadDevices();
    }

    public function generateToken()
    {
        $this->secret_token = bin2hex(random_bytes(16));
    }

    public function loadDevices()
    {
        $this->devices = DB::table('devices')
            ->select('devices.*')
            ->selectSub(
                DeviceCommand::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('device_commands.no_sn', 'devices.no_sn')
                    ->where('status', 'pending'),
                'antrean'
            )
            ->orderBy('online', 'DESC')
            ->get();
    }

    public function store()
    {
        $this->validate([
            'no_sn' => 'required|unique:devices,no_sn',
        ]);

        DB::table('devices')->insert([
            'no_sn' => $this->no_sn,
            'nama' => $this->nama,
            'lokasi' => $this->lokasi,
            'secret_token' => $this->secret_token ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session()->flash('message', 'Device Berhasil Ditambahkan.');
        $this->resetFields();
        $this->loadDevices();
    }

    public function edit($id)
    {
        $device = DB::table('devices')->where('id', $id)->first();
        $this->edit_id = $device->id;
        $this->no_sn = $device->no_sn;
        $this->nama = $device->nama;
        $this->lokasi = $device->lokasi;
        $this->secret_token = $device->secret_token ?? '';
        $this->isEdit = true;
    }

    public function update()
    {
        DB::table('devices')->where('id', $this->edit_id)->update([
            'nama' => $this->nama,
            'lokasi' => $this->lokasi,
            'secret_token' => $this->secret_token ?: null,
            'updated_at' => now(),
        ]);

        session()->flash('message', 'Device Berhasil Diperbarui.');
        $this->resetFields();
        $this->loadDevices();
    }

    public function delete($id)
    {
        DB::table('devices')->where('id', $id)->delete();
        session()->flash('message', 'Device Berhasil Dihapus.');
        $this->loadDevices();
    }

    public function bukaPerintah($id): void
    {
        $device = Device::find($id);

        if (! $device) {
            return;
        }

        $this->perintahDeviceId = $device->id;
        $this->perintahJenis = 'hapus_user';
        $this->perintahPin = '';
        $this->konfirmasi = '';
        $this->resetErrorBag();
        $this->muatRiwayat();
    }

    public function tutupPerintah(): void
    {
        $this->perintahDeviceId = null;
        $this->perintahJenis = 'hapus_user';
        $this->perintahPin = '';
        $this->konfirmasi = '';
        $this->riwayat = [];
        $this->resetErrorBag();
    }

    public function muatRiwayat(): void
    {
        $device = Device::find($this->perintahDeviceId);

        $this->riwayat = $device
            ? DeviceCommand::untukMesin($device->no_sn)->latest('id')->limit(10)->get()
            : [];
    }

    public function kirimPerintah(): void
    {
        $device = Device::find($this->perintahDeviceId);

        if (! $device) {
            $this->addError('perintah', 'Mesin tidak ditemukan. Muat ulang halaman ini.');

            return;
        }

        // Konfirmasi ketik SN: perintah di bawah ini menghapus data di mesin
        // dan tidak bisa dibatalkan dari aplikasi.
        if (trim($this->konfirmasi) !== $device->no_sn) {
            $this->addError('konfirmasi', 'Ketik SN mesin ('.$device->no_sn.') untuk mengonfirmasi.');

            return;
        }

        try {
            match ($this->perintahJenis) {
                'hapus_user' => PerintahMesin::hapusUser($device, (string) $this->perintahPin),
                'hapus_semua_user' => PerintahMesin::hapusSemuaUser($device),
                'hapus_log' => PerintahMesin::hapusLog($device),
                'reboot' => PerintahMesin::reboot($device),
                default => throw new InvalidArgumentException('Jenis perintah tidak dikenal.'),
            };
        } catch (InvalidArgumentException $e) {
            $this->addError('perintah', $e->getMessage());

            return;
        }

        $this->perintahPin = '';
        $this->konfirmasi = '';
        $this->muatRiwayat();

        session()->flash('message', 'Perintah masuk antrean. Mesin menjalankannya pada polling berikutnya.');
    }

    public function resetFields()
    {
        $this->no_sn = '';
        $this->nama = '';
        $this->lokasi = '';
        $this->secret_token = '';
        $this->isEdit = false;
        $this->edit_id = null;
    }

    public function render()
    {
        return view('livewire.admin.device-management', [
            'perintahDevice' => $this->perintahDeviceId ? Device::find($this->perintahDeviceId) : null,
        ])->layout('components.layouts.app');
    }
}
