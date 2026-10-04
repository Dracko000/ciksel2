<?php

namespace App\Notifications;

use App\Models\PengajuanIjin;

class IzinDiputuskan extends AdmsNotification
{
    public function __construct(
        private PengajuanIjin $ijin,
        private string $keputusan,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $disetujui = $this->keputusan === 'disetujui';
        $jenis = $this->ijin->jenis === 'sakit' ? 'sakit' : 'izin';

        return $this->payload(
            $disetujui ? 'Izin disetujui' : 'Izin ditolak',
            'Pengajuan '.$jenis.' tanggal '.$this->ijin->tanggal_mulai.' kamu '
                .($disetujui ? 'telah disetujui.' : 'ditolak.'),
            $disetujui ? 'success' : 'danger',
            '/',
            ['status' => $this->keputusan, 'ijin_id' => $this->ijin->id]
        );
    }
}