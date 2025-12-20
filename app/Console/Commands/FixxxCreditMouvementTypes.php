<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixCreditMouvementTypes extends Command
{
    protected $signature = 'mouvements:fix-credit-types';
    protected $description = 'Corriger les types de mouvement pour distinguer octroi et remboursement';

    public function handle()
    {
        $this->info('🔧 Correction des types de mouvement crédit...');

        $corrections = [
            [
                'conditions' => [
                    'description LIKE' => '%Crédit groupe reçu%',
                    'OR description LIKE' => '%Crédit groupe reçu%',
                    'OR reference LIKE' => '%CREDIT-GRP-%'
                ],
                'nouveau_type' => 'credit_octroye',
                'commentaire' => 'Octroi crédit groupe'
            ],
            [
                'conditions' => [
                    'description LIKE' => '%Caution bloquée%',
                    'OR reference LIKE' => '%CAUTION-%'
                ],
                'nouveau_type' => 'caution_bloquee',
                'commentaire' => 'Caution bloquée'
            ],
            [
                'conditions' => [
                    'description LIKE' => '%Crédit accordé%',
                    'OR description LIKE' => '%octroi crédit%',
                    'OR reference LIKE' => '%CREDIT-IND-%'
                ],
                'nouveau_type' => 'credit_octroye',
                'commentaire' => 'Octroi crédit individuel'
            ]
        ];

        foreach ($corrections as $correction) {
            $query = DB::table('mouvements')
                ->where('type_mouvement', 'paiement_credit');
            
            foreach ($correction['conditions'] as $condition => $value) {
                if (str_contains($condition, ' LIKE')) {
                    $column = str_replace(' LIKE', '', $condition);
                    $query->orWhere($column, 'LIKE', $value);
                }
            }

            $count = $query->count();
            
            if ($count > 0) {
                $query->update([
                    'type_mouvement' => $correction['nouveau_type'],
                    'updated_at' => now()
                ]);
                
                $this->info("✅ {$count} mouvements corrigés: {$correction['commentaire']}");
                Log::info("Correction mouvements: {$count} → {$correction['nouveau_type']}");
            }
        }

        // Compter les types après correction
        $counts = DB::table('mouvements')
            ->select('type_mouvement', DB::raw('count(*) as total'))
            ->whereIn('type_mouvement', ['credit_octroye', 'caution_bloquee', 'paiement_credit'])
            ->groupBy('type_mouvement')
            ->get();

        $this->info("\n📊 Statistiques après correction:");
        foreach ($counts as $count) {
            $this->info("   {$count->type_mouvement}: {$count->total}");
        }

        $this->info("\n✅ Correction terminée!");
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}