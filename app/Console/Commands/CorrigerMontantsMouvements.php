<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Mouvement;

class CorrigerMontantsMouvements extends Command
{
    protected $signature = 'mouvements:corriger-signes';
    protected $description = 'Corrige les signes des montants dans les mouvements';

    public function handle()
    {
        $this->info("🔍 Correction des signes des mouvements...");
        
        // Types qui doivent avoir des montants POSITIFS (sans signe dans la base)
        $typesPositifs = [
            'depot_compte',
            'credit_octroye',
            'credit_groupe_recu',
            'excedent_groupe',
            'excedent_groupe_exact',
            'remboursement',
            'interet',
            'revenus_interets',
            'commission_recue',
            'bonus',
            'distribution_comptabilite',
            'paiement_salaire_charge',
            'versement_agent',
            'caution_bloquee',
            'caution_bloquee_groupe',
        ];
        
        // Types qui doivent avoir des montants POSITIFS mais seront affichés négatifs
        $typesRetraitPositifs = [
            'paiement_credit',
            'paiement_credit_groupe',
            'frais_payes_credit',
            'frais_payes_credit_groupe',
            'retrait_compte',
            'frais_service',
            'commission',
            'frais_ouverture_compte',
            'frais_gestion',
            'debit_automatique',
            'frais_adhesion', // ✅ DOIT ÊTRE POSITIF
            'paiement_credit_automatique',
            'complement_paiement_groupe',
            'achat_carnet_livre',
        ];
        
        // Types neutres (montant 0)
        $typesNeutres = [
            'transfert_sortant',
            'transfert_entrant',
            'conversion_devise_sortant',
            'conversion_devise_entrant',
            'delaisage_comptabilite',
        ];
        
        $totalCorriges = 0;
        
        // 1. Corriger les types de retrait : enlever le signe négatif
        foreach ($typesRetraitPositifs as $type) {
            $mouvements = Mouvement::where('type_mouvement', $type)
                ->where('montant', '<', 0)
                ->get();
            
            foreach ($mouvements as $mouvement) {
                $ancienMontant = $mouvement->montant;
                $nouveauMontant = abs($mouvement->montant);
                
                $mouvement->montant = $nouveauMontant;
                
                // Recalculer les soldes si nécessaire
                if ($mouvement->solde_apres < $mouvement->solde_avant) {
                    // C'est normal pour un retrait
                    $mouvement->solde_apres = $mouvement->solde_avant - $nouveauMontant;
                }
                
                $mouvement->save();
                
                $this->line("✅ {$type}: {$ancienMontant} → {$nouveauMontant}");
                $totalCorriges++;
            }
        }
        
        // 2. Vérifier que les types positifs ont bien des montants positifs
        foreach ($typesPositifs as $type) {
            $mouvements = Mouvement::where('type_mouvement', $type)
                ->where('montant', '<', 0)
                ->get();
            
            foreach ($mouvements as $mouvement) {
                $ancienMontant = $mouvement->montant;
                $nouveauMontant = abs($mouvement->montant);
                
                $mouvement->montant = $nouveauMontant;
                
                // Recalculer les soldes
                $mouvement->solde_apres = $mouvement->solde_avant + $nouveauMontant;
                $mouvement->save();
                
                $this->line("✅ {$type}: {$ancienMontant} → {$nouveauMontant}");
                $totalCorriges++;
            }
        }
        
        // 3. Corriger les types neutres (devraient être 0)
        foreach ($typesNeutres as $type) {
            Mouvement::where('type_mouvement', $type)
                ->where('montant', '!=', 0)
                ->update(['montant' => 0]);
        }
        
        $this->info("\n🎯 {$totalCorriges} mouvements corrigés");
        
        // 4. Vérifier les incohérences
        $this->verifierIncoherences();
        
        return 0;
    }
    
    private function verifierIncoherences()
    {
        $this->info("\n🔍 Vérification des incohérences...");
        
        // Trouver les mouvements avec des montants négatifs qui ne devraient pas l'être
        $mouvementsNegatifs = Mouvement::where('montant', '<', 0)->get();
        
        if ($mouvementsNegatifs->count() > 0) {
            $this->warn("⚠️  Il reste " . $mouvementsNegatifs->count() . " mouvements avec montants négatifs:");
            
            foreach ($mouvementsNegatifs as $mouvement) {
                $this->line("  • ID {$mouvement->id}: {$mouvement->type_mouvement} = {$mouvement->montant} USD");
            }
        } else {
            $this->info("✅ Tous les montants sont positifs !");
        }
    }
}