<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Déclare les commandes artisan personnalisées
     */
    protected $commands = [
        // ici tu peux lister tes commandes
        // \App\Console\Commands\VerifierRemboursements::class,
    ];

    /**
     * Planifie les commandes
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('credits:verifier')->weeklyOn(1, '08:00'); // chaque lundi à 8h

         $schedule->command('chaine:virement-quotidien')
        ->dailyAt('18:00')
        ->timezone('Africa/Kinshasa');
    }

    /**
     * Déclare les commandes disponibles dans Artisan
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
