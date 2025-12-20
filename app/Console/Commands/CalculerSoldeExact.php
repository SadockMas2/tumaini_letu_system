<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\MouvementHelper;

class CalculerSoldeExact extends Command
{
    protected $signature = 'calculer:solde-exact 
                           {compte : Numéro du compte}
                           {--corriger : Appliquer la correction}';
    
    protected $description = 'Calcule le solde EXACTEMENT selon la vraie logique';
    
    public function handle()
    {
        $compteNum = $this->argument('compte');
        $corriger = $this->option('corriger');
        
        $compte = DB::table('comptes')
            ->where('numero_compte', $compteNum)
            ->first();
            
        if (!$compte) {
            $this->error("❌ Compte {$compteNum} non trouvé");
            return 1;
        }
        
        $this->info("🔍 COMPTE {$compteNum}");
        $this->info("💰 Solde actuel : " . number_format($compte->solde, 2));
        
        // Récupérer tous les mouvements
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compte->id)
            ->orderBy('date_mouvement', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'type_mouvement', 'montant', 'description', 'solde_avant', 'solde_apres']);
        
        // CALCUL EXACT selon les règles réelles
        $soldeCalcule = 0;
        $details = [];
        $problemes = [];
        
        foreach ($mouvements as $m) {
            $avant = $soldeCalcule;
            
            // RÈGLE 1 : Certains types ont des montants DÉJÀ SIGNÉS correctement
            $typesMontantsDejaSignes = [
                'frais_payes_credit',      // Ex: -44.00
                'frais_payes_credit_groupe',
                'paiement_credit',         // Ex: -75.48
                'paiement_credit_groupe'
            ];
            
            if (in_array($m->type_mouvement, $typesMontantsDejaSignes)) {
                // Le montant est DÉJÀ négatif, on l'AJOUTE simplement
                $soldeCalcule += $m->montant;
                $operation = 'AJOUT (déjà signé)';
            } 
            // RÈGLE 2 : Pour les autres, utiliser MouvementHelper
            else {
                $typeAffichage = MouvementHelper::getTypeAffichage($m->type_mouvement);
                
                if ($typeAffichage === 'depot') {
                    // DÉPÔT : ajouter le montant (positif)
                    $soldeCalcule += $m->montant;
                    $operation = 'DÉPÔT (+)';
                } elseif ($typeAffichage === 'retrait') {
                    // RETRAIT : soustraire le montant (positif)
                    $soldeCalcule -= $m->montant;
                    $operation = 'RETRAIT (-)';
                } else {
                    // NEUTRE/AUTRE : ajouter tel quel
                    $soldeCalcule += $m->montant;
                    $operation = 'NEUTRE';
                }
            }
            
            $apres = $soldeCalcule;
            
            // Vérifier l'incohérence avec les soldes enregistrés
            if (abs($m->solde_apres - $apres) > 0.01) {
                $problemes[] = [
                    'id' => $m->id,
                    'type' => $m->type_mouvement,
                    'solde_enregistre' => $m->solde_apres,
                    'solde_calcule' => $apres,
                    'difference' => $apres - $m->solde_apres
                ];
            }
            
            $details[] = [
                'id' => $m->id,
                'type' => $m->type_mouvement,
                'montant' => number_format($m->montant, 2),
                'avant' => number_format($avant, 2),
                'apres' => number_format($apres, 2),
                'operation' => $operation,
                'enregistre' => number_format($m->solde_apres, 2)
            ];
        }
        
        $soldeCalcule = round($soldeCalcule, 2);
        $differenceTotale = $soldeCalcule - $compte->solde;
        
        $this->info("💰 Solde calculé : " . number_format($soldeCalcule, 2));
        $this->info("📈 Différence totale : " . number_format($differenceTotale, 2));
        
        // Afficher les problèmes
        if (!empty($problemes)) {
            $this->warn("\n🚨 " . count($problemes) . " INCOHÉRENCES DÉTECTÉES :");
            
            $this->table(
                ['ID Mouvement', 'Type', 'Solde enregistré', 'Solde calculé', 'Différence'],
                array_map(function($p) {
                    return [
                        $p['id'],
                        $p['type'],
                        number_format($p['solde_enregistre'], 2),
                        number_format($p['solde_calcule'], 2),
                        number_format($p['difference'], 2)
                    ];
                }, $problemes)
            );
        }
        
        // Afficher les 100 premiers mouvements
        $this->info("\n📋 100 PREMIERS MOUVEMENTS :");
        $this->table(
            ['ID', 'Type', 'Montant', 'Avant', 'Après', 'Opération', 'Enregistré'],
            array_slice($details, 0, 100)
        );
        
        // Vérification spécifique pour frais_payes_credit
        $this->verifierFraisPayesCredit($mouvements);
        
        // Proposer la correction
        if (abs($differenceTotale) > 0.01 && !$corriger) {
            $this->warn("\n⚠️  Le solde est incorrect !");
            $this->info("Pour corriger : php artisan calculer:solde-exact {$compteNum} --corriger");
        }
        
        // Appliquer la correction si demandé
        if ($corriger && abs($differenceTotale) > 0.01) {
            return $this->appliquerCorrection($compte, $soldeCalcule, $mouvements);
        }
        
        return 0;
    }
    
    private function verifierFraisPayesCredit($mouvements)
    {
        $this->info("\n🔍 VÉRIFICATION DES frais_payes_credit :");
        
        $frais = collect($mouvements)->where('type_mouvement', 'frais_payes_credit');
        
        if ($frais->isEmpty()) {
            $this->line("  • Aucun frais_payes_credit trouvé");
            return;
        }
        
        foreach ($frais as $fraisItem) {
            $typeAffichage = MouvementHelper::getTypeAffichage($fraisItem->type_mouvement);
            $signe = MouvementHelper::getSigne($fraisItem->type_mouvement, $fraisItem->montant);
            
            $this->line("  • ID {$fraisItem->id}: Montant = {$fraisItem->montant}, " .
                "Affichage = {$typeAffichage}, Signe = {$signe}");
            
            // Explication du calcul
            if ($fraisItem->montant < 0) {
                $this->line("    → Montant NÉGATIF dans base (-" . abs($fraisItem->montant) . ")");
                $this->line("    → Calcul : solde += ({$fraisItem->montant}) = solde - " . abs($fraisItem->montant));
            } else {
                $this->line("    → Montant POSITIF dans base (+" . $fraisItem->montant . ")");
                $this->line("    → Calcul : solde -= {$fraisItem->montant} = solde - " . $fraisItem->montant);
            }
        }
    }
    
    private function appliquerCorrection($compte, $nouveauSolde, $mouvements)
    {
        $this->warn("\n⚠️  CORRECTION EN COURS...");
        
        if (!$this->confirm("Remplacer {$compte->solde} par {$nouveauSolde} ?")) {
            $this->error('❌ Annulé');
            return 1;
        }
        
        try {
            DB::beginTransaction();
            
            // 1. Mettre à jour le solde du compte
            DB::table('comptes')
                ->where('id', $compte->id)
                ->update(['solde' => $nouveauSolde]);
            
            $this->info("✅ Solde du compte mis à jour");
            
            // 2. Recalculer les soldes des mouvements
            $soldeCourant = 0;
            
            foreach ($mouvements as $m) {
                $soldeAvant = $soldeCourant;
                
                // Même logique que pour le calcul
                $typesMontantsDejaSignes = [
                    'frais_payes_credit',
                    'frais_payes_credit_groupe',
                    'paiement_credit',
                    'paiement_credit_groupe'
                ];
                
                if (in_array($m->type_mouvement, $typesMontantsDejaSignes)) {
                    $soldeCourant += $m->montant;
                } else {
                    $typeAffichage = MouvementHelper::getTypeAffichage($m->type_mouvement);
                    
                    if ($typeAffichage === 'depot') {
                        $soldeCourant += $m->montant;
                    } elseif ($typeAffichage === 'retrait') {
                        $soldeCourant -= $m->montant;
                    } else {
                        $soldeCourant += $m->montant;
                    }
                }
                
                DB::table('mouvements')
                    ->where('id', $m->id)
                    ->update([
                        'solde_avant' => $soldeAvant,
                        'solde_apres' => $soldeCourant,
                        'updated_at' => now()
                    ]);
            }
            
            $this->info("✅ Soldes des mouvements recalculés");
            
            DB::commit();
            
            $this->info("\n🎯 CORRECTION TERMINÉE AVEC SUCCÈS !");
            $this->info("   Ancien solde : " . number_format($compte->solde, 2));
            $this->info("   Nouveau solde : " . number_format($nouveauSolde, 2));
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Erreur : ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}