<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function ortu()
    {
        return $this->belongsTo(User::class, 'ortu_user_id');
    }

    public function pengajuanIjin()
    {
        return $this->hasMany(PengajuanIjin::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'pin');
    }
}
