<?php

namespace App\Notifications;

use App\Models\Device;

class DeviceOffline extends AdmsNotification
{
    public function __construct(
        private Device $device,
        private int $menitTanpaKontak,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $menit = $this->menitTanpaKontak;

        return $this->payload(
            'Mesin offline',
            ($this->device->nama ?: 'Mesin '.$this->device->no_sn).' tidak menghubungi server selama '
                .$menit.' menit.',
            'danger',
            '/admin/devices',
            ['device_id' => $this->device->id, 'no_sn' => $this->device->no_sn, 'menit' => $menit]
        );
    }
}