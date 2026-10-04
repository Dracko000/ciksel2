<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Portal memakai username, bukan email, karena wali kelas dan orang tua
 * lebih mudah mengingat NIP atau NIS. Username untuk guru diambil dari
 * guru.nip dan untuk siswa dari siswa.nis, sedangkan akun admin memakai
 * nama tetap. Password awal disamakan dengan username mengikuti aturan
 * operasional sekolah, sehingga kolom password tetap menyimpan hash.
 *
 * Kolom dibuat nullable karena role ortu tidak memiliki NIP maupun NIS.
 * Akun tanpa username tidak dapat masuk sampai username diisi manual.
 */
return new class extends Migration
{
    private const ADMIN_USERNAME = 'administrator';

    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function ($table) {
                $table->string('username', 64)->nullable()->unique()->after('name');
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'username')) {
            return;
        }

        Schema::table('users', function ($table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    /**
     * Username yang sudah terpakai dilewati agar tidak bentrok dan tidak
     * menggagalkan seluruh migrasi. Akun yang terlewat dilaporkan agar
     * bisa ditindaklanjuti secara manual.
     */
    private function backfill(): void
    {
        foreach ($this->kandidat() as $userId => $username) {
            if ($username === null || $username === '') {
                continue;
            }

            $terpakai = DB::table('users')
                ->where('username', $username)
                ->where('id', '!=', $userId)
                ->exists();

            if ($terpakai) {
                continue;
            }

            DB::table('users')->where('id', $userId)->update([
                'username' => $username,
                'password' => Hash::make($username),
            ]);
        }
    }

    /**
     * @return array<int, string|null>
     */
    private function kandidat(): array
    {
        $hasil = [];

        foreach (DB::table('users')->pluck('role', 'id') as $id => $role) {
            $hasil[$id] = match ($role) {
                'admin' => self::ADMIN_USERNAME,
                'guru' => $this->dari('guru', 'nip', $id),
                'siswa' => $this->dari('siswa', 'nis', $id),
                default => null,
            };
        }

        return $hasil;
    }

    private function dari(string $tabel, string $kolom, int $userId): ?string
    {
        if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, $kolom)) {
            return null;
        }

        $nilai = DB::table($tabel)->where('user_id', $userId)->value($kolom);

        return $nilai === null || $nilai === '' ? null : (string) $nilai;
    }
};
