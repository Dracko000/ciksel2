<?php

namespace App\Livewire\Admin;

use App\Models\Guru;
use App\Support\Absensi\JamGuru;
use Livewire\Component;

/**
 * Atur jam masuk/pulang guru & tenaga kependidikan secara perorangan.
 *
 * Setiap guru punya jam pribadinya sendiri yang dipakai sebagai acuan
 * keterlambatan. Guru yang dibiarkan kosong tidak dinilai telat, dan wali
 * kelas yang belum diatur tetap mengikuti jam kelas yang diampu.
 */
class AturJamGuru extends Component
{
    /**
     * guru_id => ['masuk' => 'HH:MM', 'pulang' => 'HH:MM'].
     *
     * @var array<int, array{masuk: string, pulang: string}>
     */
    public array $jadwal = [];

    public function mount(): void
    {
        foreach (Guru::orderBy('nama')->get() as $guru) {
            $this->jadwal[$guru->id] = [
                'masuk' => JamGuru::jam($guru->jam_masuk) ?? '',
                'pulang' => JamGuru::jam($guru->jam_pulang) ?? '',
            ];
        }
    }

    public function save(): void
    {
        $this->validate([
            'jadwal.*.masuk' => ['nullable', 'date_format:H:i'],
            'jadwal.*.pulang' => ['nullable', 'date_format:H:i'],
        ], [], [
            'jadwal.*.masuk' => 'jam masuk',
            'jadwal.*.pulang' => 'jam pulang',
        ]);

        foreach ($this->jadwal as $guruId => $jam) {
            Guru::whereKey($guruId)->update([
                'jam_masuk' => $jam['masuk'] ?: null,
                'jam_pulang' => $jam['pulang'] ?: null,
            ]);
        }

        session()->flash('message', 'Jam guru berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.admin.atur-jam-guru', [
            'gurus' => Guru::orderBy('nama')->get(),
        ])->layout('components.layouts.app');
    }
}
