<?php

namespace App\Livewire\Siswa;

use Carbon\Carbon;
use Livewire\Component;

class AbsensiDetail extends Component
{
    public function render()
    {
        $siswa = auth()->user()->siswa;
        $attendances = collect();
        if ($siswa) {
            $attendances = $siswa->attendances()
                ->orderBy('timestamp', 'desc')
                ->limit(100)
                ->get()
                ->groupBy(function ($item) {
                    return Carbon::parse($item->timestamp)->format('Y-m-d');
                })
                ->map(function ($items, $date) {
                    $masuk = $items->where('jenis', 'masuk')->sortBy('timestamp')->first();
                    $pulang = $items->where('jenis', 'pulang')->sortBy('timestamp')->last();
                    return [
                        'date' => $date,
                        'masuk' => $masuk,
                        'pulang' => $pulang,
                        'total' => $items->count(),
                    ];
                })
                ->take(30);
        }

        return view('livewire.siswa.absensi-detail', [
            'absensi' => $attendances,
        ])->layout('components.layouts.app', ['title' => 'Detail Absensi']);
    }
}
