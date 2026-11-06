<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jam masuk/pulang per tingkat kelas yang bisa diatur admin lewat menu "Atur
 * Jam". Jam guru kini diatur per orang di kolom `guru.jam_masuk` / `guru.jam_pulang`.
 *
 * @property string $kategori selalu 'kelas'
 * @property int|null $tingkat Tingkat kelas (1..6)
 * @property string|null $jam_masuk
 * @property string|null $jam_pulang
 */
class PengaturanJam extends Model
{
    protected $table = 'pengaturan_jam';

    protected $fillable = ['kategori', 'tingkat', 'jam_masuk', 'jam_pulang'];

    protected $casts = [
        'tingkat' => 'integer',
    ];
}
