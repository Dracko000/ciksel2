<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function siswa()
    {
        return $this->hasOne(Siswa::class);
    }

    public function guru()
    {
        return $this->hasOne(Guru::class);
    }

    /**
     * Resolve a user from a login identifier that may be a NIS (siswa),
     * a NIP (guru), a full email address, or the local part of an email.
     *
     * Returns null when nothing matches or when the identifier is
     * ambiguous (resolves to more than one user).
     */
    public static function findByUsername($username): ?self
    {
        $username = trim((string) $username);

        if ($username === '') {
            return null;
        }

        $like = addcslashes($username, '%_\\').'@%';

        $ids = array_merge(
            // Kolom username adalah kunci login utama: berisi NIS/NIP untuk
            // siswa/guru dan "administrator" untuk admin.
            self::where('username', $username)->pluck('id')->all(),
            Siswa::where('nis', $username)->pluck('user_id')->all(),
            Guru::where('nip', $username)->pluck('user_id')->all(),
            self::where('email', $username)->pluck('id')->all(),
            self::where('email', 'like', $like)->pluck('id')->all(),
        );

        $ids = array_values(array_unique(array_filter($ids)));

        return count($ids) === 1 ? self::find($ids[0]) : null;
    }
}
