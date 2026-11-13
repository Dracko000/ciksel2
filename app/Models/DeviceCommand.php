<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu perintah yang dikirim ke mesin ZKTeco.
 *
 * Status:
 * - pending : menunggu diambil mesin pada polling berikutnya
 * - sent    : sudah dikirim, menunggu laporan balik
 * - done    : mesin melapor berhasil
 * - failed  : mesin menolak / melapor gagal
 */
class DeviceCommand extends Model
{
    protected $fillable = [
        'device_id',
        'no_sn',
        'command',
        'status',
        'return_code',
        'result',
        'sent_at',
        'finished_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function scopeUntukMesin($query, string $noSn)
    {
        return $query->where('no_sn', $noSn);
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', 'pending');
    }
}
