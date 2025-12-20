<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifierEtCorrigerSoldes extends Command
{
    protected $signature = 'verifier:corriger-soldes 
                           {--compte= : Numéro de compte}
                           {--test : Test seulement}
                           {--revert : Annuler les dernières corrections}';
    
    protected $description = 'Vérifie et corrige les soldes CORRECTEMENT';
    
    public function handle()
    {
        $test = $this->option('test');
        $revert = $this->option('revert');
        $compteNum = $this->option('compte');
        
        if ($revert) {
            return $this->annulerCorrections();
        }
        
        $this->info('🎯 VÉRIFICATION ET CORRECTION DES SOLDES');
        $this->info('=========================================');
        
        // Récupérer les comptes
        $query = DB::table('comptes')->select('id', 'numero_compte', 'solde');
        
        if ($compteNum) {
            $query->where('numero_compte', $compteNum);
        }
        
        $comptes = $query->get();
        
        $this->info("🔍 Analyse de {$comptes->count()} comptes...");
        
        $problemes = [];
        
        foreach ($comptes as $compte) {
            // 1. VÉRIFIER si les soldes des mouvements sont cohérents
            $incoherences = $this->verifierIncoherencesMouvements($compte->id);
            
            // 2. CALCULER le solde à partir des mouvements (SANS abs() !)
            $soldeCalcule = $this->calculerSoldeCorrect($compte->id);
            
            // 3. COMPARER avec le solde actuel
            $soldeActuel = $compte->solde;
            $difference = $soldeCalcule - $soldeActuel;
            
            if (!empty($incoherences) || abs($difference) > 0.01) {
                $problemes[] = [
                    'compte' => $compte,
                    'solde_actuel' => $soldeActuel,
                    'solde_calcule' => $soldeCalcule,
                    'difference' => $difference,
                    'incoherences' => $incoherences,
                    'mouvements' => DB::table('mouvements')->where('compte_id', $compte->id)->count()
                ];
                
                // Afficher les détails pour le premier compte problématique
                if (empty($problemes) && $compteNum) {
                    $this->afficherDetailCompte($compte->id);
                }
            }
        }
        
        // Afficher les résultats
        if (!empty($problemes)) {
            $this->info("\n🚨 " . count($problemes) . " COMPTES AVEC PROBLÈMES");
            
            $this->table(
                ['Compte', 'Solde actuel', 'Solde calculé', 'Différence', 'Mouvements', 'Incohérences'],
                array_map(function($p) {
                    return [
                        $p['compte']->numero_compte,
                        number_format($p['solde_actuel'], 2),
                        number_format($p['solde_calcule'], 2),
                        number_format($p['difference'], 2),
                        $p['mouvements'],
                        count($p['incoherences'])
                    ];
                }, $problemes)
            );
            
            if (!$test) {
                $this->corrigerProblemes($problemes);
            } else {
                $this->warn("\n🔍 MODE TEST - Aucune modification");
                $this->info("Pour corriger : php artisan verifier:corriger-soldes");
            }
        } else {
            $this->info("\n✅ Tous les soldes sont corrects !");
        }
        
        return 0;
    }
    
    private function calculerSoldeCorrect($compteId)
    {
        // SIMPLE : somme des montants (déjà signés correctement)
        $somme = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->sum('montant');
            
        return round($somme, 2);
    }
    
    private function verifierIncoherencesMouvements($compteId)
    {
        $incoherences = [];
        
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'montant', 'solde_avant', 'solde_apres']);
        
        $soldeAttendu = 0;
        
        foreach ($mouvements as $index => $mouvement) {
            // Vérifier si solde_avant correspond
            if ($index > 0 && abs($mouvement->solde_avant - $soldeAttendu) > 0.01) {
                $incoherences[] = [
                    'mouvement_id' => $mouvement->id,
                    'solde_avant_attendu' => $soldeAttendu,
                    'solde_avant_reel' => $mouvement->solde_avant,
                    'difference' => $mouvement->solde_avant - $soldeAttendu
                ];
            }
            
            // Calculer le solde après attendu
            $soldeAttendu += $mouvement->montant;
            
            // Vérifier si solde_apres correspond
            if (abs($mouvement->solde_apres - $soldeAttendu) > 0.01) {
                $incoherences[] = [
                    'mouvement_id' => $mouvement->id,
                    'solde_apres_attendu' => $soldeAttendu,
                    'solde_apres_reel' => $mouvement->solde_apres,
                    'difference' => $mouvement->solde_apres - $soldeAttendu
                ];
            }
        }
        
        return $incoherences;
    }
    
    private function afficherDetailCompte($compteId)
    {
        $this->info("\n📋 DÉTAIL DU CALCUL POUR LE COMPTE #{$compteId}:");
        
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->limit(15)
            ->get(['type_mouvement', 'montant', 'description']);
        
        $solde = 0;
        
        $this->table(
            ['Opération', 'Type', 'Montant', 'Solde avant', 'Solde après', 'Description'],
            $mouvements->map(function($m) use (&$solde) {
                $avant = $solde;
                $solde += $m->montant;
                $apres = $solde;
                
                return [
                    $m->montant >= 0 ? 'DÉPÔT' : 'RETRAIT',
                    $m->type_mouvement,
                    number_format($m->montant, 2),
                    number_format($avant, 2),
                    number_format($apres, 2),
                    substr($m->description ?? '', 0, 30)
                ];
            })->toArray()
        );
        
        $total = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->sum('montant');
            
        $this->info("\n💰 TOTAL : " . number_format($total, 2));
    }
    
    private function corrigerProblemes($problemes)
    {
        $this->warn("\n⚠️  CORRECTION EN COURS...");
        
        $corriges = 0;
        
        foreach ($problemes as $probleme) {
            $compte = $probleme['compte'];
            
            // 1. Corriger le solde du compte
            DB::table('comptes')
                ->where('id', $compte->id)
                ->update(['solde' => $probleme['solde_calcule']]);
            
            // 2. Corriger les soldes des mouvements
            $this->corrigerSoldesMouvements($compte->id);
            
            $corriges++;
            
            $this->line("  ✅ {$compte->numero_compte} : " . 
                number_format($probleme['solde_actuel'], 2) . " → " . 
                number_format($probleme['solde_calcule'], 2));
        }
        
        $this->info("\n✅ {$corriges} comptes corrigés !");
    }
    
    private function corrigerSoldesMouvements($compteId)
    {
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'montant']);
        
        $soldeCourant = 0;
        
        foreach ($mouvements as $mouvement) {
            $soldeAvant = $soldeCourant;
            $soldeCourant += $mouvement->montant;
            
            DB::table('mouvements')
                ->where('id', $mouvement->id)
                ->update([
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $soldeCourant,
                    'updated_at' => now()
                ]);
        }
    }
    
    private function annulerCorrections()
    {
        $this->info('🔄 Annulation des corrections...');
        
        // Ici, vous devriez avoir une sauvegarde des soldes avant correction
        // Sinon, on ne peut pas annuler
        
        $this->error('❌ Impossible d\'annuler sans sauvegarde préalable.');
        $this->info('Contactez votre administrateur de base de données.');
        
        return 1;
    }
}   