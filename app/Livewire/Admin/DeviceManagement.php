<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class DeviceManagement extends Component
{
    public $devices;
    public $edit_id;
    public $no_sn, $nama, $lokasi, $secret_token;
    public $isEdit = false;

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
        $this->devices = DB::table('devices')->orderBy('online', 'DESC')->get();
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

    public function resetFields()
    {
        $this->no_sn = ''; $this->nama = ''; $this->lokasi = ''; $this->secret_token = '';
        $this->isEdit = false; $this->edit_id = null;
    }

    public function render()
    {
        return view('livewire.admin.device-management')->layout('components.layouts.app');
    }
}
