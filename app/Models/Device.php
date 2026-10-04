<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama',
        'no_sn',
        'lokasi',
        'online',
    ];

    protected $casts = [
        'online' => 'datetime',
    ];
}
