<?php

namespace App\Notifications;

use App\Models\Ekstrakulikuler;

class JadwalMendatang extends AdmsNotification
{
    public function __construct(
        private Ekstrakulikuler $ekstrakulikuler,
        private string $peran,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $item = $this->ekstrakulikuler;

        $waktu = $item->jam_mulai
            ? substr((string) $item->jam_mulai, 0, 5)
                .($item->jam_selesai ? '-'.substr((string) $item->jam_selesai, 0, 5) : '')
            : '';

        return $this->payload(
            'Ekstrakulikuler hari ini',
            ($this->peran === 'pembina' ? 'Anda membina ' : 'Anda mengikuti ')
                .$item->nama
                .($item->hari ? ' ('.$item->hari.')' : '')
                .($waktu ? ' pukul '.$waktu : '')
                .($item->lokasi ? ' di '.$item->lokasi : '').'.',
            'info',
            '/admin/ekstrakulikuler',
            ['ekstrakulikuler_id' => $item->id, 'peran' => $this->peran]
        );
    }
}