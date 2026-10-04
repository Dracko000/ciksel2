<?php

namespace App\Console\Commands\Aman;

use App\Console\Commands\PeriksaDatabaseProduksi;
use Illuminate\Database\Console\Migrations\RefreshCommand;

/**
 * migrate:refresh bawaan Laravel, dengan pemeriksaan database produksi.
 */
class MigrateRefreshAman extends RefreshCommand
{
    use PeriksaDatabaseProduksi;
}
