<?php

namespace App\Livewire\Siswa;

use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $siswa = auth()->user()->siswa;

        // Kehadiran dihitung dari log masuk saja (tap pulang tidak ikut
        // dihitung supaya tidak menggandakan). attendance.status1 dipakai mesin
        // ZKTeco sebagai kode hadir; selain 0 dihitung sebagai kehadiran.
        $hadir = $siswa?->attendances()->where('jenis', 'masuk')->where('status1', '!=', 0)->count() ?? 0;

        $ekstrakulikuler = collect();
        if ($siswa) {
            $ekstrakulikuler = DB::table('ekstrakulikuler_siswa')
                ->join('ekstrakulikuler', 'ekstrakulikuler_siswa.ekstrakulikuler_id', '=', 'ekstrakulikuler.id')
                ->where('ekstrakulikuler_siswa.siswa_id', $siswa->id)
                ->where('ekstrakulikuler.aktif', true)
                ->select('ekstrakulikuler.id', 'ekstrakulikuler.nama', 'ekstrakulikuler.deskripsi', 'ekstrakulikuler.hari', 'ekstrakulikuler.jam_mulai', 'ekstrakulikuler.jam_selesai', 'ekstrakulikuler.lokasi', 'ekstrakulikuler.tahun_ajaran', 'ekstrakulikuler.semester')
                ->orderBy('ekstrakulikuler.nama')
                ->get();
        }

        $absenHariIni = null;
        $notifikasiAbsen = null;
        if ($siswa) {
            $today = now()->format('Y-m-d');
            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();
            $att = $siswa->attendances()
                ->whereBetween('timestamp', [$todayStart, $todayEnd])
                ->orderBy('timestamp')
                ->get();
            $masuk = $att->where('jenis', 'masuk')->first();
            $pulang = $att->where('jenis', 'pulang')->last();
            $absenHariIni = [
                'masuk' => $masuk?->timestamp,
                'pulang' => $pulang?->timestamp,
            ];

            if ($masuk && !$pulang) {
                $notifikasiAbsen = [
                    'tipe' => 'masuk',
                    'waktu' => $masuk->timestamp,
                    'pesan' => 'Anda sudah absen masuk hari ini.',
                ];
            } elseif ($pulang) {
                $notifikasiAbsen = [
                    'tipe' => 'pulang',
                    'waktu' => $pulang->timestamp,
                    'pesan' => 'Anda sudah absen pulang hari ini.',
                ];
            }
        }

        return view('livewire.siswa.dashboard', [
            'siswa' => $siswa,
            'kelas' => $siswa?->kelas_id ? Kelas::find($siswa->kelas_id) : null,
            'hadir' => $hadir,
            'total' => $siswa?->attendances()->where('jenis', 'masuk')->count() ?? 0,
            'terakhir' => $siswa?->attendances()->latest('timestamp')->limit(5)->get() ?? collect(),
            'ekstrakulikuler' => $ekstrakulikuler,
            'absenHariIni' => $absenHariIni,
            'notifikasiAbsen' => $notifikasiAbsen,
        ])->layout('components.layouts.app', ['title' => 'Dashboard Siswa']);
    }
}