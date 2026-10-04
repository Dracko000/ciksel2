<?php

namespace App\Notifications;

use App\Models\PengajuanIjin;

class IzinDiajukan extends AdmsNotification
{
    public function __construct(private PengajuanIjin $ijin)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $siswa = $this->ijin->siswa;

        $jenis = $this->ijin->jenis === 'sakit' ? 'Sakit' : 'Izin';

        return $this->payload(
            'Pengajuan izin baru',
            ($siswa?->nama ?? 'Siswa').' mengajukan '.$jenis.' tanggal '.$this->ijin->tanggal_mulai
                .($this->ijin->tanggal_selesai && $this->ijin->tanggal_selesai !== $this->ijin->tanggal_mulai
                    ? ' sampai '.$this->ijin->tanggal_selesai
                    : '').'.',
            'warning',
            '/admin/konfirmasi',
            ['jenis' => $this->ijin->jenis, 'siswa_id' => $this->ijin->siswa_id]
        );
    }
}