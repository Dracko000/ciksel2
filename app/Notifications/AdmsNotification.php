<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dasar semua notifikasi ADMS.
 *
 * Semua notifikasi hanya dikirim ke dalam aplikasi (in-app) lewat kanal
 * database, tanpa email atau push. Payload memakai kunci "judul" dan "pesan"
 * karena itulah yang dibaca lonceng notifikasi.
 */
abstract class AdmsNotification extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(object $notifiable): array;

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function payload(string $judul, string $pesan, string $level = 'info', string $url = '/', array $extra = []): array
    {
        return array_merge([
            'judul' => $judul,
            'pesan' => $pesan,
            'level' => $level,
            'url' => $url,
        ], $extra);
    }
}