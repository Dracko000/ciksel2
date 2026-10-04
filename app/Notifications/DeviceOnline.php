<?php

namespace App\Notifications;

use App\Models\Device;

class DeviceOnline extends AdmsNotification
{
    public function __construct(private Device $device)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload(
            'Mesin kembali online',
            ($this->device->nama ?: 'Mesin '.$this->device->no_sn).' terhubung kembali'
                .($this->device->lokasi ? ' di '.$this->device->lokasi : '').'.',
            'success',
            '/admin/devices',
            ['device_id' => $this->device->id, 'no_sn' => $this->device->no_sn]
        );
    }
}