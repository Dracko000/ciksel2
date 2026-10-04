<?php

namespace App\Console\Commands\Aman;

use App\Console\Commands\PeriksaDatabaseProduksi;
use Illuminate\Database\Console\WipeCommand;

/**
 * db:wipe bawaan Laravel, dengan pemeriksaan database produksi.
 */
class DbWipeAman extends WipeCommand
{
    use PeriksaDatabaseProduksi;
}
