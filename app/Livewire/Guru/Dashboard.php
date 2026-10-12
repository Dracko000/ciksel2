<?php

namespace App\Livewire\Guru;

use Livewire\Component;
use App\Models\InformasiSekolah;
use App\Models\JadwalPelajaran;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $guru = $user->guru;
        $guruId = $guru->id ?? null;
        $jadwalHariIni = JadwalPelajaran::with('kelas')
            ->where('guru_id', $guruId)
            // ->where('hari', now()->locale('id')->dayName) // Uncomment jika sudah punya data real
            ->orderBy('jam_mulai')
            ->get();

        $pengumuman = collect();
        if ($guru) {
            $kelasIds = $guru->kelasWali()->pluck('id')->toArray();
            $query = InformasiSekolah::with('kelas', 'author')
                ->where(function ($q) use ($kelasIds) {
                    $q->where('tujuan', 'umum')
                      ->orWhere(function ($q2) use ($kelasIds) {
                          $q2->where('tujuan', 'kelas')->whereIn('kelas_id', $kelasIds);
                      });
                })
                ->whereDate('tanggal_publikasi', '<=', now());
            $pengumuman = $query->latest()->take(10)->get();
        } else {
            $pengumuman = InformasiSekolah::with('kelas', 'author')
                ->where('tujuan', 'umum')
                ->whereDate('tanggal_publikasi', '<=', now())
                ->latest()
                ->take(10)
                ->get();
        }

        return view('livewire.guru.dashboard', [
            'jadwalHariIni' => $jadwalHariIni,
            'pengumuman' => $pengumuman,
        ])->layout('components.layouts.app');
    }
}
