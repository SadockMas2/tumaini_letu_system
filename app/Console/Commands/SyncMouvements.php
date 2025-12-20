<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMouvements extends Command
{
    protected $signature = 'mouvements:sync';
    protected $description = 'Synchroniser les type_mouvement basés sur le type et la description';

    public function handle()
    {
        $this->info('Début de la synchronisation des mouvements...');
        $totalUpdated = 0;

        DB::transaction(function () use (&$totalUpdated) {
            // Liste des mappages type -> type_mouvement basé sur la description
            $mappings = [
                // Dépôts
                'depot' => [
                    'patterns' => [
                        ['pattern' => '%remboursement%', 'type_mouvement' => 'depot_compte'],
                        ['pattern' => '%DEPOT%', 'type_mouvement' => 'depot_compte'],
                        ['pattern' => '%depot%', 'type_mouvement' => 'depot_compte'],
                        ['pattern' => '%caution%', 'type_mouvement' => 'depot_caution'],
                        ['pattern' => '%distribution%', 'type_mouvement' => 'distribution_comptabilite'],
                        ['pattern' => '%comptabilité%', 'type_mouvement' => 'distribution_comptabilite'],
                        ['pattern' => '%APPROVISIO%', 'type_mouvement' => 'distribution_comptabilite'],
                    ]
                ],
                // Retraits
                'retrait' => [
                    'patterns' => [
                        ['pattern' => '%RETRAIT%', 'type_mouvement' => 'retrait_compte'],
                        ['pattern' => '%retrait%', 'type_mouvement' => 'retrait_compte'],
                        ['pattern' => '%CREDIT%', 'type_mouvement' => 'paiement_credit'],
                        ['pattern' => '%credit%', 'type_mouvement' => 'paiement_credit'],
                    ]
                ]
            ];

            foreach ($mappings as $type => $mapping) {
                foreach ($mapping['patterns'] as $pattern) {
                    $updated = DB::table('mouvements')
                        ->whereNull('type_mouvement')
                        ->where('type', $type)
                        ->where('description', 'like', $pattern['pattern'])
                        ->update([
                            'type_mouvement' => $pattern['type_mouvement'],
                            'updated_at' => now()
                        ]);

                    if ($updated > 0) {
                        $this->info("Mis à jour {$updated} mouvements: {$type} avec pattern '{$pattern['pattern']}' -> {$pattern['type_mouvement']}");
                        $totalUpdated += $updated;
                    }
                }
            }

            // Pour les cas restants sans description spécifique
            $remainingDepots = DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->where('type', 'depot')
                ->count();

            if ($remainingDepots > 0) {
                $updated = DB::table('mouvements')
                    ->whereNull('type_mouvement')
                    ->where('type', 'depot')
                    ->update([
                        'type_mouvement' => 'depot_compte',
                        'updated_at' => now()
                    ]);
                $this->info("Mis à jour {$updated} dépôts restants en 'depot_compte'");
                $totalUpdated += $updated;
            }

            $remainingRetraits = DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->where('type', 'retrait')
                ->count();

            if ($remainingRetraits > 0) {
                $updated = DB::table('mouvements')
                    ->whereNull('type_mouvement')
                    ->where('type', 'retrait')
                    ->update([
                        'type_mouvement' => 'retrait_compte',
                        'updated_at' => now()
                    ]);
                $this->info("Mis à jour {$updated} retraits restants en 'retrait_compte'");
                $totalUpdated += $updated;
            }
        });

        $this->info("Synchronisation terminée. Total mis à jour: {$totalUpdated}");
        Log::info("Synchronisation des mouvements terminée", ['total_updated' => $totalUpdated]);

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}