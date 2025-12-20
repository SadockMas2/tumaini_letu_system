<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\MouvementHelper;

class CorrigerSoldesFinal extends Command
{
    protected $signature = 'corriger:soldes-final 
                           {--compte= : Numéro de compte}
                           {--test : Voir sans corriger}
                           {--annuler : Annuler les dernières corrections}';
    
    protected $description = 'Corrige les soldes FINALEMENT selon la vraie logique';
    
    public function handle()
    {
        $test = $this->option('test');
        $annuler = $this->option('annuler');
        $compteNum = $this->option('compte');
        
        if ($annuler) {
            $this->annulerCorrections();
            return 0;
        }
        
        $this->info('🎯 CORRECTION FINALE DES SOLDES');
        $this->info('===============================');
        
        if ($test) {
            $this->warn('🔍 MODE TEST - Aucune modification');
        } else {
            $this->warn('⚠️  ATTENTION : Les soldes seront corrigés !');
            
            if (!$this->confirm('Êtes-vous sûr de vouloir corriger ?')) {
                $this->error('❌ Annulé');
                return 1;
            }
        }
        
        // Récupérer les comptes
        $query = DB::table('comptes')->select('id', 'numero_compte', 'solde');
        
        if ($compteNum) {
            $query->where('numero_compte', $compteNum);
        }
        
        $comptes = $query->get();
        
        $this->info("🔍 Analyse de {$comptes->count()} comptes...");
        
        $corrections = [];
        
        foreach ($comptes as $compte) {
            // 1. CALCULER LE VRAI SOLDE selon votre logique
            $soldeCorrect = $this->calculerSoldeVrai($compte->id);
            
            // 2. COMPARER
            $soldeActuel = $compte->solde;
            $difference = $soldeCorrect - $soldeActuel;
            
            if (abs($difference) > 0.01) {
                $corrections[] = [
                    'compte' => $compte,
                    'solde_actuel' => $soldeActuel,
                    'solde_correct' => $soldeCorrect,
                    'difference' => $difference
                ];
                
                if (!$test) {
                    // Corriger le solde
                    DB::table('comptes')
                        ->where('id', $compte->id)
                        ->update(['solde' => $soldeCorrect]);
                    
                    // Corriger les soldes des mouvements
                    $this->corrigerSoldesMouvements($compte->id);
                }
            }
        }
        
        // Afficher les résultats
        if (!empty($corrections)) {
            $this->info("\n📊 " . count($corrections) . " COMPTES À CORRIGER");
            
            $this->table(
                ['Compte', 'Solde actuel', 'Solde correct', 'Différence', 'Statut'],
                array_map(function($c) use ($test) {
                    return [
                        $c['compte']->numero_compte,
                        number_format($c['solde_actuel'], 2),
                        number_format($c['solde_correct'], 2),
                        number_format($c['difference'], 2),
                        $test ? 'À CORRIGER' : '✅ CORRIGÉ'
                    ];
                }, $corrections)
            );
            
            if ($test) {
                $this->warn("\n🔍 Mode TEST - Pour appliquer :");
                $this->info("php artisan corriger:soldes-final");
            } else {
                $this->info("\n✅ Correction terminée !");
            }
            
            // Afficher un exemple de calcul
            if (!empty($corrections)) {
                $this->afficherExempleCalcul($corrections[0]['compte']->id);
            }
        } else {
            $this->info("\n✅ Tous les soldes sont déjà corrects !");
        }
        
        return 0;
    }
    
    /**
     * CALCULER LE VRAI SOLDE selon votre logique
     * Tous les montants sont positifs dans la base !
     */
    private function calculerSoldeVrai($compteId)
    {
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->orderBy('id', 'asc')
            ->get(['type_mouvement', 'montant']);
        
        $solde = 0;
        
        foreach ($mouvements as $mouvement) {
            // Le montant est TOUJOURS positif dans votre base
            $montant = $mouvement->montant;
            
            // Déterminer si c'est un dépôt ou retrait
            $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
            
            if ($typeAffichage === 'depot') {
                // DÉPÔT : AJOUTER le montant
                $solde += $montant;
            } elseif ($typeAffichage === 'retrait') {
                // RETRAIT : SOUSTRAIRE le montant
                $solde -= $montant;
            } elseif ($typeAffichage === 'neutre') {
                // NEUTRE : pour caution_bloquee (montant 0), ne rien faire
                // Pour les autres neutres, ajouter le montant (peut être positif ou négatif)
                $solde += $montant;
            } else {
                // AUTRE : ajouter le montant (déjà signé)
                $solde += $montant;
            }
        }
        
        return round($solde, 2);
    }
    
    private function corrigerSoldesMouvements($compteId)
    {
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'type_mouvement', 'montant']);
        
        $soldeCourant = 0;
        
        foreach ($mouvements as $mouvement) {
            $soldeAvant = $soldeCourant;
            
            // Appliquer la même logique
            $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
            
            if ($typeAffichage === 'depot') {
                $soldeCourant += $mouvement->montant;
            } elseif ($typeAffichage === 'retrait') {
                $soldeCourant -= $mouvement->montant;
            } elseif ($typeAffichage === 'neutre') {
                $soldeCourant += $mouvement->montant;
            } else {
                $soldeCourant += $mouvement->montant;
            }
            
            DB::table('mouvements')
                ->where('id', $mouvement->id)
                ->update([
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $soldeCourant
                ]);
        }
    }
    
    private function afficherExempleCalcul($compteId)
    {
        $this->info("\n📋 EXEMPLE DE CALCUL POUR LE COMPTE #{$compteId}:");
        
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->limit(10)
            ->get(['type_mouvement', 'montant', 'description']);
        
        $solde = 0;
        
        foreach ($mouvements as $mouvement) {
            $avant = $solde;
            $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
            
            if ($typeAffichage === 'depot') {
                $solde += $mouvement->montant;
                $operation = "+ {$mouvement->montant}";
            } elseif ($typeAffichage === 'retrait') {
                $solde -= $mouvement->montant;
                $operation = "- {$mouvement->montant}";
            } else {
                $solde += $mouvement->montant;
                $operation = $mouvement->montant >= 0 ? "+ {$mouvement->montant}" : "- " . abs($mouvement->montant);
            }
            
            $this->line(sprintf(
                "  %s %s = %s  |  %-20s (%-8s) %s",
                number_format($avant, 2),
                $operation,
                number_format($solde, 2),
                $mouvement->type_mouvement,
                $typeAffichage,
                substr($mouvement->description ?? '', 0, 30)
            ));
        }
    }
    
    private function annulerCorrections()
    {
        $this->info('🔄 Annulation des corrections...');
        
        // Sauvegarde manuelle nécessaire
        $this->error('❌ Impossible d\'annuler sans sauvegarde.');
        $this->info('Contactez votre administrateur de base de données.');
        
        return 1;
    }
}