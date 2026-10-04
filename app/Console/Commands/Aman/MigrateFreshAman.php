<?php

namespace App\Console\Commands\Aman;

use App\Console\Commands\PeriksaDatabaseProduksi;
use Illuminate\Database\Console\Migrations\FreshCommand;

/**
 * migrate:fresh bawaan Laravel, dengan pemeriksaan database produksi.
 */
class MigrateFreshAman extends FreshCommand
{
    use PeriksaDatabaseProduksi;
}
