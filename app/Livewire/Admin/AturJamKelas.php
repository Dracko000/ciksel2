<?php

namespace App\Livewire\Admin;

use App\Models\PengaturanJam;
use App\Support\Absensi\JamKelas;
use Livewire\Component;

/**
 * Atur jam masuk/pulang per tingkat kelas.
 *
 * Nilai ini langsung dipakai sistem untuk menilai keterlambatan dan menebak
 * jenis tap masuk/pulang mesin ZKTeco.
 */
class AturJamKelas extends Component
{
    /**
     * Tingkat => ['masuk' => 'HH:MM', 'pulang' => 'HH:MM'].
     *
     * @var array<int, array{masuk: string, pulang: string}>
     */
    public array $jadwal = [];

    public function mount(): void
    {
        $tersimpan = PengaturanJam::where('kategori', 'kelas')
            ->whereNotNull('tingkat')
            ->get()
            ->keyBy('tingkat');

        foreach (JamKelas::tingkatTersedia() as $tingkat) {
            $bawaan = JamKelas::defaultTingkat($tingkat) ?? ['masuk' => '', 'pulang' => ''];
            $row = $tersimpan->get($tingkat);

            $this->jadwal[$tingkat] = [
                'masuk' => $row?->jam_masuk ? substr((string) $row->jam_masuk, 0, 5) : $bawaan['masuk'],
                'pulang' => $row?->jam_pulang ? substr((string) $row->jam_pulang, 0, 5) : $bawaan['pulang'],
            ];
        }
    }

    public function save(): void
    {
        $this->validate([
            'jadwal.*.masuk' => ['required', 'date_format:H:i'],
            'jadwal.*.pulang' => ['required', 'date_format:H:i'],
        ], [], [
            'jadwal.*.masuk' => 'jam masuk',
            'jadwal.*.pulang' => 'jam pulang',
        ]);

        foreach ($this->jadwal as $tingkat => $jam) {
            PengaturanJam::updateOrCreate(
                ['kategori' => 'kelas', 'tingkat' => (int) $tingkat],
                ['jam_masuk' => $jam['masuk'], 'jam_pulang' => $jam['pulang']],
            );
        }

        JamKelas::resetCache();

        session()->flash('message', 'Jam belajar kelas berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.admin.atur-jam-kelas', [
            'daftarTingkat' => JamKelas::tingkatTersedia(),
        ])->layout('components.layouts.app');
    }
}
