<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ekstrakulikuler extends Model
{
    use HasFactory;

    protected $table = 'ekstrakulikuler';
    protected $guarded = ['id'];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Kolom pivot punya foreign key cascade, tapi dilepas juga di sini agar
        // peserta ikut terhapus walaupun migrasi belum dijalankan.
        static::deleting(function (self $item): void {
            $item->siswa()->detach();
        });
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function siswa()
    {
        return $this->belongsToMany(Siswa::class, 'ekstrakulikuler_siswa', 'ekstrakulikuler_id', 'siswa_id')
            ->withPivot(['tahun_ajaran', 'semester'])
            ->withTimestamps();
    }

    /**
     * Kunci pivot dipakai saatsoidempoten: satu siswa hanya boleh terdaftar
     * satu kali untuk ekstrakulikuler yang sama.
     */
    public function scopeForSiswa($query, Siswa $siswa)
    {
        return $query->whereHas('siswa', fn ($q) => $q->where('siswa.id', $siswa->id));
    }
}