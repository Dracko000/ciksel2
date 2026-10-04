<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Mesin absensi menghubungi server terus-menerus, sehingga device yang
        // diam perlu dipantau tiap beberapa menit, dan pengingat jadwal dikirim
        // sekali pagi sebelum kegiatan dimulai.
        $schedule->command('adms:device-heartbeat')->everyFiveMinutes();
        $schedule->command('adms:device-heartbeat', ['--jadwal' => true])
            ->weekdays()
            ->dailyAt('06:45');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
