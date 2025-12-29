<?php

namespace App\Console\Commands;

use App\Models\Cycle;
use Illuminate\Console\Command;

class SyncCycleEpargnesCount extends Command
{
    protected $signature = 'cycles:sync-epargnes-count';
    protected $description = 'Synchroniser le compteur d\'épargnes avec la réalité';

    public function handle()
    {
        $cycles = Cycle::all();
        
        $this->info("Synchronisation de {$cycles->count()} cycles...");
        
        $bar = $this->output->createProgressBar($cycles->count());
        
        foreach ($cycles as $cycle) {
            $cycle->synchroniserCompteurEpargnes();
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\nSynchronisation terminée !");
        
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}