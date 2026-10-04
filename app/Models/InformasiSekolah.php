<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformasiSekolah extends Model
{
    use HasFactory;

    protected $table = 'informasi_sekolah';
    protected $guarded = ['id'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
