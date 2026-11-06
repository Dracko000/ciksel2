<?php

namespace Tests;

use App\Support\Absensi\JamKelas;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Jadwal kelas disimpan di cache statis per proses. Bersihkan tiap test
        // supaya pengaturan dari satu test tidak bocor ke test lain.
        JamKelas::resetCache();
    }
}
