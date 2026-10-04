<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression test untuk proteksi database produksi.
 *
 * Proteksi versi pertama memakai Event::listen(CommandStarting::class) di
 * AppServiceProvider. Cara itu ternyata dilewati begitu saja: ketika artisan
 * dijalankan dengan `--env=testing`, listener tersebut tidak pernah dipanggil,
 * padahal config database tetap menunjuk ke "adms". Akibatnya `migrate:fresh`
 * menghapus seluruh isi database sekolah tanpa terhalang.
 *
 * Test ini menjaga agar kegagalan seperti itu terdeteksi lagi. Pengujiannya
 * tidak pernah menyentuh database sungguhan: target perintah diarahkan ke
 * database khusus test, dan nama database itulah yang dijadikan target terlindungi.
 */
class DatabaseGuardTest extends TestCase
{
    /**
     * Database specifically untuk test ini. Kosong dan bisa dibuang sewaktu-waktu.
     */
    private const DB_UJI = 'adms_guard_test';

    private const KONEKSI_UJI = 'adms_guard_test';

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE DATABASE IF NOT EXISTS '.self::DB_UJI);

        config([
            'database.connections.'.self::KONEKSI_UJI => [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => self::DB_UJI,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'strict' => true,
            ],
        ]);

        $this->setEnv('ADMS_PRODUCTION_DATABASE', self::DB_UJI);
        $this->setEnv('ADMS_ALLOW_DESTRUCTIVE', null);

        DB::connection(self::KONEKSI_UJI)->statement(
            'CREATE TABLE guard_probe (id INT PRIMARY KEY) '
        );
        DB::connection(self::KONEKSI_UJI)->table('guard_probe')->insert(['id' => 1]);
    }

    protected function tearDown(): void
    {
        $this->setEnv('ADMS_PRODUCTION_DATABASE', null);
        $this->setEnv('ADMS_ALLOW_DESTRUCTIVE', null);

        DB::purge(self::KONEKSI_UJI);
        DB::statement('DROP DATABASE IF EXISTS '.self::DB_UJI);

        parent::tearDown();
    }

    /**
     * @dataProvider perintahDestruktif
     */
    public function test_perintah_destructive_ditolak_pada_database_terlindungi(string $perintah): void
    {
        $kode = $this->artisan($perintah, [
            '--database' => self::KONEKSI_UJI,
            '--force' => true,
        ])->run();

        $this->assertSame(1, $kode, "Perintah {$perintah} seharusnya ditolak.");

        $this->assertTrue(
            $this->tabelMasihAda(),
            "Tabel hilang: {$perintah} berhasil menghapus database terlindungi."
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function perintahDestruktif(): array
    {
        return [
            'migrate:fresh' => ['migrate:fresh'],
            'migrate:refresh' => ['migrate:refresh'],
            'migrate:reset' => ['migrate:reset'],
            'db:wipe' => ['db:wipe'],
        ];
    }

    public function test_database_biasa_tetap_boleh_dikosongkan(): void
    {
        // Target terlindungi diganti ke nama lain, sehingga database uji boleh
        // dikosongkan seperti biasa.
        $this->setEnv('ADMS_PRODUCTION_DATABASE', 'adms');

        $this->artisan('migrate:fresh', [
            '--database' => self::KONEKSI_UJI,
            '--force' => true,
        ])->run();

        $this->assertFalse(
            $this->tabelMasihAda(),
            'migrate:fresh seharusnya tetap bisa jalan pada database pengujian.'
        );
    }

    public function test_izin_env_membuka_perintah(): void
    {
        $this->setEnv('ADMS_ALLOW_DESTRUCTIVE', 'true');

        // migrate:fresh, bukan migrate:reset: perintah reset hanya rollback
        // tabel migrasi sehingga tidak menyentuh tabel biasa sama sekali.
        $this->artisan('migrate:fresh', [
            '--database' => self::KONEKSI_UJI,
            '--force' => true,
        ])->run();

        $this->assertFalse(
            $this->tabelMasihAda(),
            'Dengan ADMS_ALLOW_DESTRUCTIVE=true perintah seharusnya boleh jalan.'
        );
    }

    private function tabelMasihAda(): bool
    {
        $hasil = DB::selectOne(
            'SELECT COUNT(*) AS jumlah FROM information_schema.tables
             WHERE table_schema = ? AND table_name = ?',
            [self::DB_UJI, 'guard_probe']
        );

        return (int) $hasil->jumlah === 1;
    }

    private function setEnv(string $nama, ?string $nilai): void
    {
        if ($nilai === null) {
            putenv($nama);
            unset($_ENV[$nama], $_SERVER[$nama]);

            return;
        }

        putenv("{$nama}={$nilai}");
        $_ENV[$nama] = $nilai;
        $_SERVER[$nama] = $nilai;
    }
}