<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanIjin extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_ijin';
    protected $guarded = ['id'];
    protected $appends = ['foto_bukti_url'];

    public function getFotoBuktiUrlAttribute()
    {
        if (! empty($this->foto_bukti)) {
            return asset('storage/'.$this->foto_bukti);
        }

        return null;
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }
}
