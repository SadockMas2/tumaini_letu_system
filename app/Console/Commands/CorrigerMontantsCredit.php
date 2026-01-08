<?php

namespace App\Console\Commands;

use App\Models\Credit;
use App\Models\CreditGroupe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorrigerMontantsCredit extends Command
{
    protected $signature = 'credits:corriger 
                            {--type=all : Type de crédit à corriger (individuel, groupe, all)}
                            {--id= : ID spécifique d\'un crédit}
                            {--dry-run : Afficher seulement ce qui sera corrigé sans modifier la BD}';
    
    protected $description = 'Corriger les montants totaux des crédits selon les formules';

    public function handle()
    {
        $type = $this->option('type');
        $id = $this->option('id');
        $dryRun = $this->option('dry-run');
        
        $this->info("=== CORRECTION DES MONTANTS DE CRÉDITS ===");
        $this->info("Date: " . now()->format('d/m/Y H:i:s'));
        $this->info("Mode: " . ($dryRun ? 'SIMULATION (dry-run)' : 'RÉELLE'));
        $this->newLine();
        
        $correctionsEffectuees = 0;
        $erreurs = 0;
        
        if ($type === 'groupe' || $type === 'all') {
            $correctionsGroupe = $this->corrigerCreditsGroupe($id, $dryRun);
            $correctionsEffectuees += $correctionsGroupe['corrections'];
            $erreurs += $correctionsGroupe['erreurs'];
        }
        
        if ($type === 'individuel' || $type === 'all') {
            $correctionsIndividuel = $this->corrigerCreditsIndividuels($id, $dryRun);
            $correctionsEffectuees += $correctionsIndividuel['corrections'];
            $erreurs += $correctionsIndividuel['erreurs'];
        }
        
        $this->newLine();
        $this->info("=== RÉSUMÉ DE LA CORRECTION ===");
        $this->info("Corrections effectuées: {$correctionsEffectuees}");
        $this->info("Erreurs rencontrées: {$erreurs}");
        $this->info("Mode: " . ($dryRun ? 'SIMULATION - Aucune modification réelle' : 'CORRECTIONS APPLIQUÉES'));
        
        if (!$dryRun && $correctionsEffectuees > 0) {
            $this->info("✅ Les montants ont été corrigés dans la base de données.");
        }
    }
    
    private function corrigerCreditsGroupe($id = null, $dryRun = false)
    {
        $this->info("📊 CORRECTION CRÉDITS DE GROUPE");
        $this->info(str_repeat('-', 80));
        
        $query = CreditGroupe::where('statut_demande', 'approuve');
        
        if ($id) {
            $query->where('id', $id);
        }
        
        $credits = $query->get();
        
        if ($credits->isEmpty()) {
            $this->warn("Aucun crédit groupe trouvé");
            return ['corrections' => 0, 'erreurs' => 0];
        }
        
        $corrections = 0;
        $erreurs = 0;
        
        foreach ($credits as $credit) {
            try {
                // Calcul selon votre formule : montant_accorde * 1.225
                $montantAccorde = floatval($credit->montant_accorde);
                $montantCalcule = $montantAccorde * 1.225;
                $montantActuel = floatval($credit->montant_total);
                
                // Arrondir à 2 décimales
                $montantCalcule = round($montantCalcule, 2);
                
                // Vérifier si une correction est nécessaire
                $difference = abs($montantCalcule - $montantActuel);
                
                if ($difference > 0.01) { // Tolérance de 0.01 USD
                    $this->info("ID: {$credit->id}");
                    $nomGroupe = $credit->compte ? $credit->compte->nom : 'N/A';
                    $this->info("  Groupe: {$nomGroupe}");
                    $this->info("  Montant accordé: " . number_format($montantAccorde, 2) . " USD");
                    $this->info("  Montant actuel: " . number_format($montantActuel, 2) . " USD");
                    $this->info("  Montant calculé (×1.225): " . number_format($montantCalcule, 2) . " USD");
                    $this->info("  Différence: " . number_format($montantCalcule - $montantActuel, 2) . " USD");
                    
                    if (!$dryRun) {
                        // Appliquer la correction
                        $credit->montant_total = $montantCalcule;
                        
                        // Recalculer le remboursement hebdomadaire
                        $credit->remboursement_hebdo_total = $montantCalcule / 16;
                        
                        $credit->save();
                        
                        $this->info("  ✅ CORRIGÉ: Nouveau montant total = " . number_format($montantCalcule, 2) . " USD");
                        
                        // Log de la correction
                        Log::info("Correction crédit groupe", [
                            'credit_id' => $credit->id,
                            'ancien_montant' => $montantActuel,
                            'nouveau_montant' => $montantCalcule,
                            'difference' => $difference
                        ]);
                    } else {
                        $this->info("  📋 SIMULATION: Serait corrigé à " . number_format($montantCalcule, 2) . " USD");
                    }
                    
                    $corrections++;
                    $this->newLine();
                }
            } catch (\Exception $e) {
                $this->error("❌ Erreur pour crédit groupe ID {$credit->id}: " . $e->getMessage());
                $erreurs++;
                Log::error("Erreur correction crédit groupe", [
                    'credit_id' => $credit->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        if ($corrections == 0) {
            $this->info("✅ Tous les crédits groupe ont déjà les bonnes valeurs.");
        }
        
        return ['corrections' => $corrections, 'erreurs' => $erreurs];
    }
    
    private function corrigerCreditsIndividuels($id = null, $dryRun = false)
    {
        $this->info("👤 CORRECTION CRÉDITS INDIVIDUELS");
        $this->info(str_repeat('-', 80));
        
        $query = Credit::where('statut_demande', 'approuve')
                      ->where('type_credit', 'individuel');
        
        if ($id) {
            $query->where('id', $id);
        }
        
        $credits = $query->get();
        
        if ($credits->isEmpty()) {
            $this->warn("Aucun crédit individuel trouvé");
            return ['corrections' => 0, 'erreurs' => 0];
        }
        
        $corrections = 0;
        $erreurs = 0;
        
        foreach ($credits as $credit) {
            try {
                $montantAccorde = floatval($credit->montant_accorde);
                $montantCalcule = $this->calculerMontantTotalIndividuel($montantAccorde);
                $montantActuel = floatval($credit->montant_total);
                
                // Arrondir à 2 décimales
                $montantCalcule = round($montantCalcule, 2);
                
                // Vérifier si une correction est nécessaire
                $difference = abs($montantCalcule - $montantActuel);
                
                if ($difference > 0.01) { // Tolérance de 0.01 USD
                    $this->info("ID: {$credit->id}");
                    $nomClient = $credit->compte ? $credit->compte->nom : 'N/A';
                    $this->info("  Client: {$nomClient}");
                    $this->info("  Montant accordé: " . number_format($montantAccorde, 2) . " USD");
                    $this->info("  Montant actuel: " . number_format($montantActuel, 2) . " USD");
                    $this->info("  Montant calculé: " . number_format($montantCalcule, 2) . " USD");
                    $this->info("  Différence: " . number_format($montantCalcule - $montantActuel, 2) . " USD");
                    
                    $pourcentage = $this->getPourcentageParTranche($montantAccorde);
                    $this->info("  Pourcentage appliqué: {$pourcentage}");
                    
                    if (!$dryRun) {
                        // Appliquer la correction
                        $credit->montant_total = $montantCalcule;
                        
                        // Recalculer le remboursement hebdomadaire
                        $credit->remboursement_hebdo = $montantCalcule / 16;
                        
                        $credit->save();
                        
                        $this->info("  ✅ CORRIGÉ: Nouveau montant total = " . number_format($montantCalcule, 2) . " USD");
                        
                        // Log de la correction
                        Log::info("Correction crédit individuel", [
                            'credit_id' => $credit->id,
                            'ancien_montant' => $montantActuel,
                            'nouveau_montant' => $montantCalcule,
                            'difference' => $difference,
                            'pourcentage' => $pourcentage
                        ]);
                    } else {
                        $this->info("  📋 SIMULATION: Serait corrigé à " . number_format($montantCalcule, 2) . " USD");
                    }
                    
                    $corrections++;
                    $this->newLine();
                }
            } catch (\Exception $e) {
                $this->error("❌ Erreur pour crédit individuel ID {$credit->id}: " . $e->getMessage());
                $erreurs++;
                Log::error("Erreur correction crédit individuel", [
                    'credit_id' => $credit->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        if ($corrections == 0) {
            $this->info("✅ Tous les crédits individuels ont déjà les bonnes valeurs.");
        }
        
        return ['corrections' => $corrections, 'erreurs' => $erreurs];
    }
    
    private function calculerMontantTotalIndividuel($montant)
    {
        // Reprendre votre logique du modèle
        if ($montant >= 100 && $montant <= 500) {
            return $montant * 0.308666 * 4;
        } elseif ($montant >= 501 && $montant <= 1000) {
            return $montant * 0.3019166667 * 4;
        } elseif ($montant >= 1001 && $montant <= 1599) {
            return $montant * 0.30866 * 4;
        } elseif ($montant >= 2000 && $montant <= 5000) {
            return $montant * 0.2985666667 * 4;
        }
        return $montant * 0.30 * 4; // Par défaut
    }
    
    private function getPourcentageParTranche($montant)
    {
        // Retourne le pourcentage selon la tranche
        if ($montant >= 100 && $montant <= 500) {
            return "30.8666%";
        } elseif ($montant >= 501 && $montant <= 1000) {
            return "30.19166667%";
        } elseif ($montant >= 1001 && $montant <= 1599) {
            return "30.866%";
        } elseif ($montant >= 2000 && $montant <= 5000) {
            return "29.85666667%";
        }
        return "30%";
    }
}