<?php

namespace App\Console\Commands;

use App\Models\Mouvement;
use App\Models\Compte;
use App\Helpers\MouvementHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorrigerSoldesMouvements extends Command
{
    protected $signature = 'mouvements:corriger-soldes 
                            {--compte= : ID du compte spécifique}
                            {--mouvement= : ID du mouvement spécifique}
                            {--dry-run : Simuler sans appliquer les modifications}
                            {--force : Forcer la réinitialisation du solde à 0 avant recalcul}
                            {--debug : Afficher le détail de chaque mouvement}';
    
    protected $description = 'Corriger les soldes avant/après des mouvements selon l\'ordre chronologique';

    public function handle()
    {
        $compteId = $this->option('compte');
        $mouvementId = $this->option('mouvement');
        $dryRun = $this->option('dry-run');
        $forceReset = $this->option('force');
        $debug = $this->option('debug');
        
        $this->info("=== CORRECTION DES SOLDES DES MOUVEMENTS ===");
        $this->info("Date: " . now()->format('d/m/Y H:i:s'));
        $this->info("Mode: " . ($dryRun ? 'SIMULATION (dry-run)' : 'RÉELLE'));
        $this->info("Utilise MouvementHelper pour déterminer les types");
        $this->newLine();
        
        if ($mouvementId) {
            return $this->corrigerMouvementUnique($mouvementId, $dryRun, $debug);
        }
        
        if ($compteId) {
            return $this->corrigerCompteUnique($compteId, $dryRun, $forceReset, $debug);
        }
        
        return $this->corrigerTousLesComptes($dryRun, $forceReset, $debug);
    }
    
    /**
     * Corriger un compte unique avec la LOGIQUE CORRECTE
     */
    private function corrigerCompteUnique($compteId, $dryRun, $forceReset, $debug)
    {
        $compte = Compte::find($compteId);
        
        if (!$compte) {
            $this->error("Compte {$compteId} non trouvé");
            return 1;
        }
        
        $this->info("=== CORRECTION DU COMPTE ===");
        $this->info("Compte: {$compte->numero_compte}");
        $this->info("Client: {$compte->nom} {$compte->prenom}");
        $this->info("Solde actuel: " . number_format($compte->solde, 2));
        $this->newLine();
        
        // Récupérer tous les mouvements du compte
        $mouvements = Mouvement::where('compte_id', $compteId)
            ->orWhere('numero_compte', $compte->numero_compte)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        
        if ($mouvements->isEmpty()) {
            $this->info("Aucun mouvement trouvé pour ce compte");
            return 0;
        }
        
        $corrections = 0;
        $incohérences = 0;
        $soldeCourant = $forceReset ? 0 : 0; // Partir de 0 et tout recalculer
        
        $this->info("Début du recalcul des soldes avec MouvementHelper...");
        $this->info("Nombre de mouvements: " . $mouvements->count());
        
        if ($debug) {
            $this->info("\n=== DÉBOGAGE DÉTAILLÉ ===");
        }
        
        // Tableau pour suivre le solde à chaque étape
        $historiqueSoldes = [];
        
        foreach ($mouvements as $index => $mouvement) {
            $soldeAvant = $soldeCourant;
            
            // UTILISER MouvementHelper pour déterminer l'effet sur le solde
            $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
            $montant = (float) $mouvement->montant;
            
            // LOGIQUE CORRECTE selon MouvementHelper
            if ($typeAffichage === 'depot') {
                // Dépôt : ajouter le montant
                $soldeCourant += $montant;
                $operation = '+';
            } elseif ($typeAffichage === 'retrait') {
                // Retrait : soustraire le montant
                $soldeCourant -= $montant;
                $operation = '-';
            } elseif ($typeAffichage === 'neutre') {
                // Mouvement neutre : ne pas modifier le solde
                // Sauf cas spéciaux...
                if ($mouvement->type_mouvement === 'caution_bloquee' && $montant > 0) {
                    $soldeCourant -= $montant; // La caution bloque une partie du solde
                    $operation = '-';
                } else {
                    $operation = '=';
                }
            } else {
                // Autre : par défaut ajouter
                $soldeCourant += $montant;
                $operation = '+';
            }
            
            $soldeApres = $soldeCourant;
            
            // Stocker pour le debug
            $historiqueSoldes[] = [
                'id' => $mouvement->id,
                'type' => $mouvement->type_mouvement,
                'type_affichage' => $typeAffichage,
                'montant' => $montant,
                'operation' => $operation,
                'solde_avant_calcule' => $soldeAvant,
                'solde_apres_calcule' => $soldeApres,
                'solde_avant_enregistre' => $mouvement->solde_avant,
                'solde_apres_enregistre' => $mouvement->solde_apres,
            ];
            
            // Vérifier les incohérences
            $differenceAvant = abs($mouvement->solde_avant - $soldeAvant);
            $differenceApres = abs($mouvement->solde_apres - $soldeApres);
            
            if ($differenceAvant > 0.01 || $differenceApres > 0.01) {
                $incohérences++;
                
                if ($debug) {
                    $this->info("\n--- Incohérence détectée ---");
                    $this->info("Mouvement #" . ($index + 1) . " - ID: {$mouvement->id}");
                    $this->info("Date: {$mouvement->created_at->format('d/m/Y H:i:s')}");
                    $this->info("Type mouvement: {$mouvement->type_mouvement}");
                    $this->info("Type affichage (Helper): {$typeAffichage}");
                    $this->info("Montant: " . number_format($montant, 2) . " ({$operation})");
                    $this->info("Référence: {$mouvement->reference}");
                    $this->info("Description: " . substr($mouvement->description ?? 'N/A', 0, 50) . "...");
                    $this->info("Ancien: " . number_format($mouvement->solde_avant, 2) . " → " . number_format($mouvement->solde_apres, 2));
                    $this->info("Nouveau: " . number_format($soldeAvant, 2) . " → " . number_format($soldeApres, 2));
                    $this->info("Différence avant: " . number_format($differenceAvant, 2));
                    $this->info("Différence après: " . number_format($differenceApres, 2));
                }
                
                if (!$dryRun) {
                    $mouvement->solde_avant = $soldeAvant;
                    $mouvement->solde_apres = $soldeApres;
                    $mouvement->save();
                    $corrections++;
                    
                    if ($debug) {
                        $this->info("✅ CORRIGÉ");
                    }
                } else {
                    if ($debug) {
                        $this->info("📋 SIMULATION");
                    }
                }
            } elseif ($debug) {
                $this->info("\nMouvement #" . ($index + 1) . " - ID: {$mouvement->id} - ✓ Correct");
                $this->info("  Type: {$mouvement->type_mouvement} ({$typeAffichage})");
                $this->info("  Montant: " . number_format($montant, 2) . " ({$operation})");
                $this->info("  Solde: " . number_format($soldeAvant, 2) . " → " . number_format($soldeApres, 2));
            }
        }
        
        // Afficher un résumé détaillé
        if ($debug && !empty($historiqueSoldes)) {
            $this->info("\n=== HISTORIQUE COMPLET DES SOLDES ===");
            $this->info(str_repeat('-', 120));
            $this->info(sprintf(
                "%-5s | %-20s | %-10s | %-8s | %-12s | %-12s | %-12s | %-12s",
                "ID", "Type", "Montant", "Op", "Calc Avant", "Calc Après", "Enr Avant", "Enr Après"
            ));
            $this->info(str_repeat('-', 120));
            
            foreach ($historiqueSoldes as $h) {
                $erreurAvant = abs($h['solde_avant_calcule'] - $h['solde_avant_enregistre']) > 0.01;
                $erreurApres = abs($h['solde_apres_calcule'] - $h['solde_apres_enregistre']) > 0.01;
                $style = $erreurAvant || $erreurApres ? 'error' : 'info';
                
                $this->$style(sprintf(
                    "%-5d | %-20s | %-10s | %-8s | %-12s | %-12s | %-12s | %-12s",
                    $h['id'],
                    substr($h['type'], 0, 20),
                    number_format($h['montant'], 2),
                    $h['operation'],
                    number_format($h['solde_avant_calcule'], 2),
                    number_format($h['solde_apres_calcule'], 2),
                    number_format($h['solde_avant_enregistre'], 2),
                    number_format($h['solde_apres_enregistre'], 2)
                ));
            }
        }
        
        // Mettre à jour le solde du compte
        $dernierSolde = $soldeCourant;
        $difference = $dernierSolde - $compte->solde;
        
        $this->info("\n=== RÉSUMÉ FINAL ===");
        $this->info("Solde recalculé: " . number_format($dernierSolde, 2));
        $this->info("Solde actuel: " . number_format($compte->solde, 2));
        $this->info("Différence: " . number_format($difference, 2));
        $this->info("Incohérences détectées: {$incohérences}");
        $this->info("Corrections appliquées: {$corrections}");
        
        if (!$dryRun && abs($difference) > 0.01) {
            $compte->solde = $dernierSolde;
            $compte->save();
            $this->info("✅ Solde du compte mis à jour");
        }
        
        if ($incohérences > 0 && $dryRun) {
            $this->info("\n⚠️  {$incohérences} incohérences détectées. Exécutez sans --dry-run pour les corriger.");
        }
        
        return 0;
    }
    
    /**
     * Corriger tous les comptes
     */
    private function corrigerTousLesComptes($dryRun, $forceReset, $debug)
    {
        $comptes = Compte::all();
        
        if ($comptes->isEmpty()) {
            $this->error("Aucun compte trouvé");
            return 1;
        }
        
        $this->info("Correction de " . $comptes->count() . " comptes");
        $this->newLine();
        
        $totalIncohérences = 0;
        $totalCorrections = 0;
        $totalErreurs = 0;
        
        foreach ($comptes as $compte) {
            try {
                $this->info("Traitement du compte: {$compte->numero_compte}");
                
                $mouvements = Mouvement::where('compte_id', $compte->id)
                    ->orWhere('numero_compte', $compte->numero_compte)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();
                
                if ($mouvements->isEmpty()) {
                    $this->info("  Aucun mouvement - Ignoré");
                    continue;
                }
                
                $soldeCourant = $forceReset ? 0 : 0;
                $incohérencesCompte = 0;
                $correctionsCompte = 0;
                
                foreach ($mouvements as $mouvement) {
                    $soldeAvant = $soldeCourant;
                    
                    // Utiliser MouvementHelper
                    $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
                    $montant = (float) $mouvement->montant;
                    
                    if ($typeAffichage === 'depot') {
                        $soldeCourant += $montant;
                    } elseif ($typeAffichage === 'retrait') {
                        $soldeCourant -= $montant;
                    } elseif ($typeAffichage === 'neutre' && $mouvement->type_mouvement === 'caution_bloquee' && $montant > 0) {
                        $soldeCourant -= $montant;
                    }
                    
                    $soldeApres = $soldeCourant;
                    
                    $differenceAvant = abs($mouvement->solde_avant - $soldeAvant);
                    $differenceApres = abs($mouvement->solde_apres - $soldeApres);
                    
                    if ($differenceAvant > 0.01 || $differenceApres > 0.01) {
                        $incohérencesCompte++;
                        
                        if (!$dryRun) {
                            $mouvement->solde_avant = $soldeAvant;
                            $mouvement->solde_apres = $soldeApres;
                            $mouvement->save();
                            $correctionsCompte++;
                        }
                    }
                }
                
                // Mettre à jour le solde du compte
                $dernierSolde = $soldeCourant;
                
                if (!$dryRun && abs($dernierSolde - $compte->solde) > 0.01) {
                    $compte->solde = $dernierSolde;
                    $compte->save();
                }
                
                if ($incohérencesCompte > 0) {
                    $this->info("  ⚠️  {$incohérencesCompte} incohérences, {$correctionsCompte} corrections");
                    $totalIncohérences += $incohérencesCompte;
                    $totalCorrections += $correctionsCompte;
                } else {
                    $this->info("  ✓ Aucune incohérence détectée");
                }
                
            } catch (\Exception $e) {
                $this->error("  ❌ Erreur: " . $e->getMessage());
                $totalErreurs++;
                Log::error("Erreur correction compte {$compte->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->newLine();
        $this->info("=== RÉCAPITULATIF FINAL ===");
        $this->info("Total comptes traités: " . $comptes->count());
        $this->info("Total incohérences: {$totalIncohérences}");
        $this->info("Total corrections: {$totalCorrections}");
        $this->info("Total erreurs: {$totalErreurs}");
        $this->info("Mode: " . ($dryRun ? 'SIMULATION - Aucune modification' : 'CORRECTIONS APPLIQUÉES'));
        
        if ($totalIncohérences > 0 && $dryRun) {
            $this->info("\n⚠️  Exécutez sans --dry-run pour corriger les {$totalIncohérences} incohérences.");
        }
        
        return 0;
    }
}