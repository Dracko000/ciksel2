<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'sn',
        'table',
        'stamp',
        'employee_id',
        'timestamp',
        'status1',
        'status2',
        'status3',
        'status4',
        'status5',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'status1' => 'integer',
        'status2' => 'integer',
        'status3' => 'integer',
        'status4' => 'integer',
        'status5' => 'integer',
    ];

    public function siswa()
    {
        return $this->hasOne(Siswa::class, 'wajah_id_zkteco', 'employee_id');
    }

    public function guru()
    {
        return $this->hasOne(Guru::class, 'wajah_id_zkteco', 'employee_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'sn', 'no_sn');
    }
}