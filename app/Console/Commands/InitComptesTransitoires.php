<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InitComptesTransitoires extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init-comptes-transitoires';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    // Dans app/Console/Commands/InitComptesTransitoires.php
public function handle()
{
    $agents = \App\Models\User::whereHas('roles', function($q) {
        $q->where('name', 'AgentCollecteur');
    })->get();
    
    foreach ($agents as $agent) {
        foreach (['USD', 'CDF'] as $devise) {
            \App\Models\CompteTransitoire::firstOrCreate(
                [
                    'user_id' => $agent->id,
                    'devise' => $devise
                ],
                [
                    'agent_nom' => $agent->name,
                    'solde' => 1000000, // Solde initial élevé pour tester
                    'statut' => 'actif'
                ]
            );
        }
    }
    
    $this->info('Comptes transitoires initialisés avec succès!');
}
}
