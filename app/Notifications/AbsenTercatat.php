<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class AbsenTercatat extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $jenis,
        public string $waktu,
        public string $nama,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $jenis = $this->jenis === 'masuk' ? 'Masuk' : 'Pulang';
        return [
            'title' => 'Absen ' . $jenis . ' Tercatat',
            'body' => 'Halo ' . $this->nama . ', absen ' . strtolower($jenis) . ' Anda telah tercatat pada ' . $this->waktu . ' WIB.',
            'jenis' => $this->jenis,
            'waktu' => $this->waktu,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
