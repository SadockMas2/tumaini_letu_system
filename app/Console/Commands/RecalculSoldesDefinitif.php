<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\MouvementHelper;

class RecalculSoldesDefinitif extends Command
{
    protected $signature = 'recalcul:soldes-definitif 
                           {--compte= : Numéro de compte}
                           {--test : Voir sans appliquer}
                           {--debug : Mode debug détaillé}';
    
    protected $description = 'Recalcule les soldes définitivement';
    
    public function handle()
    {
        $test = $this->option('test');
        $debug = $this->option('debug');
        $compteNum = $this->option('compte');
        
        $this->info('🎯 RECALCUL DÉFINITIF DES SOLDES');
        $this->info('=================================');
        
        if ($test) {
            $this->warn('🔍 MODE TEST - Aucune modification');
        } else {
            $this->warn('⚠️  ATTENTION : Les soldes seront corrigés !');
            
            if (!$this->confirm('Êtes-vous sûr de vouloir continuer ?')) {
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
            // Calculer le solde CORRECT
            $soldeCorrect = $this->calculerSoldeExact($compte->id, $debug);
            
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
                    $this->corrigerSoldesMouvementsExact($compte->id);
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
                $this->info("php artisan recalcul:soldes-definitif");
            } else {
                $this->info("\n✅ Correction terminée !");
            }
            
            // Afficher un exemple détaillé
            if ($debug || !empty($corrections)) {
                $this->afficherCalculDetaille($corrections[0]['compte']->id);
            }
        } else {
            $this->info("\n✅ Tous les soldes sont déjà corrects !");
        }
        
        return 0;
    }
    
    /**
     * CALCUL EXACT du solde en tenant compte de TOUS les cas
     */
    private function calculerSoldeExact($compteId, $debug = false)
    {
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->orderBy('id', 'asc')
            ->get(['type_mouvement', 'montant', 'description']);
        
        $solde = 0;
        
        if ($debug) {
            $this->info("\n🔬 DÉTAIL DU CALCUL POUR LE COMPTE #{$compteId}:");
            $this->line("Départ : 0.00");
        }
        
        foreach ($mouvements as $mouvement) {
            $avant = $solde;
            
            // RÈGLES SPÉCIFIQUES pour votre système :
            
            // 1. Certains types ont des montants NÉGATIFS dans la base
            $typesAvecMontantsNegatifs = [
                'frais_payes_credit',
                'frais_payes_credit_groupe', 
                'paiement_credit',
                'paiement_credit_groupe'
            ];
            
            // 2. Pour ces types, on AJOUTE le montant (qui est déjà négatif)
            if (in_array($mouvement->type_mouvement, $typesAvecMontantsNegatifs)) {
                $solde += $mouvement->montant; // montant déjà négatif
                $operation = "({$mouvement->type_mouvement}) " . number_format($mouvement->montant, 2);
            } 
            // 3. Pour les autres types, utiliser MouvementHelper
            else {
                $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
                
                if ($typeAffichage === 'depot') {
                    $solde += abs($mouvement->montant);
                    $operation = "(dépôt) +" . number_format(abs($mouvement->montant), 2);
                } elseif ($typeAffichage === 'retrait') {
                    $solde -= abs($mouvement->montant);
                    $operation = "(retrait) -" . number_format(abs($mouvement->montant), 2);
                } else {
                    $solde += $mouvement->montant;
                    $operation = "(neutre) " . ($mouvement->montant >= 0 ? '+' : '-') . number_format(abs($mouvement->montant), 2);
                }
            }
            
            if ($debug) {
                $this->line(sprintf(
                    "  %s %s = %s | %s",
                    number_format($avant, 2),
                    $operation,
                    number_format($solde, 2),
                    substr($mouvement->description ?? '', 0, 40)
                ));
            }
        }
        
        return round($solde, 2);
    }
    
    private function corrigerSoldesMouvementsExact($compteId)
    {
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'type_mouvement', 'montant']);
        
        $soldeCourant = 0;
        
        foreach ($mouvements as $mouvement) {
            $soldeAvant = $soldeCourant;
            
            // Même logique que pour le calcul
            $typesAvecMontantsNegatifs = [
                'frais_payes_credit',
                'frais_payes_credit_groupe', 
                'paiement_credit',
                'paiement_credit_groupe'
            ];
            
            if (in_array($mouvement->type_mouvement, $typesAvecMontantsNegatifs)) {
                $soldeCourant += $mouvement->montant;
            } else {
                $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
                
                if ($typeAffichage === 'depot') {
                    $soldeCourant += abs($mouvement->montant);
                } elseif ($typeAffichage === 'retrait') {
                    $soldeCourant -= abs($mouvement->montant);
                } else {
                    $soldeCourant += $mouvement->montant;
                }
            }
            
            DB::table('mouvements')
                ->where('id', $mouvement->id)
                ->update([
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $soldeCourant
                ]);
        }
    }
    
    private function afficherCalculDetaille($compteId)
    {
        $this->info("\n📋 CALCUL DÉTAILLÉ POUR LE COMPTE #{$compteId}:");
        
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->get(['type_mouvement', 'montant', 'description']);
        
        $this->table(
            ['Type', 'Montant dans base', 'Signe réel', 'Description'],
            $mouvements->map(function($m) {
                // Déterminer le signe réel
                $typesNegatifs = ['frais_payes_credit', 'frais_payes_credit_groupe', 'paiement_credit', 'paiement_credit_groupe'];
                
                if (in_array($m->type_mouvement, $typesNegatifs)) {
                    $signe = '-';
                } else {
                    $typeAffichage = MouvementHelper::getTypeAffichage($m->type_mouvement);
                    $signe = $typeAffichage === 'retrait' ? '-' : '+';
                }
                
                return [
                    $m->type_mouvement,
                    number_format($m->montant, 2),
                    $signe,
                    substr($m->description ?? '', 0, 40)
                ];
            })->toArray()
        );
    }
}