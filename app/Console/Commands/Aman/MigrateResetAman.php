<?php

namespace App\Console\Commands\Aman;

use App\Console\Commands\PeriksaDatabaseProduksi;
use Illuminate\Database\Console\Migrations\ResetCommand;

/**
 * migrate:reset bawaan Laravel, dengan pemeriksaan database produksi.
 */
class MigrateResetAman extends ResetCommand
{
    use PeriksaDatabaseProduksi;
}
