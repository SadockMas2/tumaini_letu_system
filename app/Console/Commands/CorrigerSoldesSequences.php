<?php

namespace App\Console\Commands;

use App\Models\Mouvement;
use App\Models\Compte;
use App\Helpers\MouvementHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorrigerSoldesSequences extends Command
{
    protected $signature = 'mouvements:corriger-sequences 
                            {--compte= : ID du compte spécifique}
                            {--dry-run : Simuler sans appliquer les modifications}
                            {--debug : Afficher le détail de chaque mouvement}';
    
    protected $description = 'Corriger les soldes pour les mouvements enregistrés à la même seconde';

    public function handle()
    {
        $compteId = $this->option('compte');
        $dryRun = $this->option('dry-run');
        $debug = $this->option('debug');
        
        $this->info("=== CORRECTION DES SOLDES POUR SÉQUENCES ===");
        $this->info("Problème: Les mouvements à la même seconde ont des soldes avant incorrects");
        $this->newLine();
        
        if ($compteId) {
            return $this->corrigerCompteSequences($compteId, $dryRun, $debug);
        }
        
        return $this->corrigerTousLesComptesSequences($dryRun, $debug);
    }
    
    /**
     * Corriger les séquences d'un compte
     */
    private function corrigerCompteSequences($compteId, $dryRun, $debug)
    {
        $compte = Compte::find($compteId);
        
        if (!$compte) {
            $this->error("Compte {$compteId} non trouvé");
            return 1;
        }
        
        $this->info("=== CORRECTION DU COMPTE ===");
        $this->info("Compte: {$compte->numero_compte}");
        $this->info("Client: {$compte->nom} {$compte->prenom}");
        $this->newLine();
        
        // Récupérer tous les mouvements du compte
        $mouvements = Mouvement::where('compte_id', $compteId)
            ->orWhere('numero_compte', $compte->numero_compte)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc') // IMPORTANT: ID pour l'ordre à l'intérieur d'une même seconde
            ->get();
        
        if ($mouvements->isEmpty()) {
            $this->info("Aucun mouvement trouvé pour ce compte");
            return 0;
        }
        
        $this->info("Analyse de " . $mouvements->count() . " mouvements...");
        $this->newLine();
        
        // Regrouper par timestamp pour trouver les séquences
        $sequences = [];
        $currentSequence = [];
        $lastTimestamp = null;
        
        foreach ($mouvements as $mouvement) {
            $timestamp = $mouvement->created_at->format('Y-m-d H:i:s');
            
            if ($timestamp !== $lastTimestamp) {
                if (!empty($currentSequence)) {
                    $sequences[] = $currentSequence;
                }
                $currentSequence = [$mouvement];
                $lastTimestamp = $timestamp;
            } else {
                $currentSequence[] = $mouvement;
            }
        }
        
        if (!empty($currentSequence)) {
            $sequences[] = $currentSequence;
        }
        
        // Identifier les séquences problématiques (plus d'un mouvement à la même seconde)
        $sequencesProblematiques = [];
        foreach ($sequences as $sequence) {
            if (count($sequence) > 1) {
                $sequencesProblematiques[] = $sequence;
            }
        }
        
        if (empty($sequencesProblematiques)) {
            $this->info("✅ Aucune séquence problématique trouvée");
            return 0;
        }
        
        $this->info("⚠️  " . count($sequencesProblematiques) . " séquences problématiques trouvées");
        $this->newLine();
        
        // Maintenant, recalculer TOUT dans l'ordre chronologique
        $soldeCourant = 0;
        $corrections = 0;
        
        if ($debug) {
            $this->info("=== RECALCUL COMPLET ===");
        }
        
        foreach ($mouvements as $index => $mouvement) {
            $soldeAvant = $soldeCourant;
            
            // Appliquer le mouvement
            $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
            $montant = (float) $mouvement->montant;
            
            if ($typeAffichage === 'depot') {
                $soldeCourant += $montant;
                $operation = '+';
            } elseif ($typeAffichage === 'retrait') {
                $soldeCourant -= $montant;
                $operation = '-';
            } elseif ($typeAffichage === 'neutre' && $mouvement->type_mouvement === 'caution_bloquee' && $montant > 0) {
                $soldeCourant -= $montant;
                $operation = '-';
            } else {
                $operation = '=';
            }
            
            $soldeApres = $soldeCourant;
            
            // Vérifier si correction nécessaire
            $differenceAvant = abs($mouvement->solde_avant - $soldeAvant);
            $differenceApres = abs($mouvement->solde_apres - $soldeApres);
            
            if ($differenceAvant > 0.01 || $differenceApres > 0.01) {
                if ($debug) {
                    $this->info("\n--- Correction nécessaire ---");
                    $this->info("Mouvement #" . ($index + 1) . " - ID: {$mouvement->id}");
                    $this->info("Date: {$mouvement->created_at->format('d/m/Y H:i:s')}");
                    $this->info("Type: {$mouvement->type_mouvement}");
                    $this->info("Ancien: " . number_format($mouvement->solde_avant, 2) . " → " . number_format($mouvement->solde_apres, 2));
                    $this->info("Nouveau: " . number_format($soldeAvant, 2) . " → " . number_format($soldeApres, 2));
                }
                
                if (!$dryRun) {
                    $mouvement->solde_avant = $soldeAvant;
                    $mouvement->solde_apres = $soldeApres;
                    $mouvement->save();
                    
                    if ($debug) {
                        $this->info("✅ CORRIGÉ");
                    }
                    
                    $corrections++;
                } else {
                    if ($debug) {
                        $this->info("📋 SIMULATION");
                    }
                }
            } elseif ($debug) {
                $this->info("\nMouvement #" . ($index + 1) . " - ID: {$mouvement->id} - ✓ Correct");
                $this->info("  Solde: " . number_format($soldeAvant, 2) . " → " . number_format($soldeApres, 2));
            }
        }
        
        // Mettre à jour le solde du compte
        $dernierSolde = $soldeCourant;
        $difference = $dernierSolde - $compte->solde;
        
        $this->newLine();
        $this->info("=== RÉSUMÉ ===");
        $this->info("Séquences problématiques: " . count($sequencesProblematiques));
        $this->info("Corrections nécessaires: {$corrections}");
        $this->info("Solde recalculé: " . number_format($dernierSolde, 2));
        $this->info("Solde actuel: " . number_format($compte->solde, 2));
        $this->info("Différence: " . number_format($difference, 2));
        
        if (!$dryRun && $corrections > 0) {
            if (abs($difference) > 0.01) {
                $compte->solde = $dernierSolde;
                $compte->save();
                $this->info("✅ Solde du compte mis à jour");
            }
            $this->info("✅ {$corrections} mouvements corrigés");
        }
        
        if ($corrections > 0 && $dryRun) {
            $this->info("\n⚠️  {$corrections} corrections nécessaires. Exécutez sans --dry-run pour appliquer.");
        }
        
        return 0;
    }
    
    /**
     * Corriger tous les comptes
     */
    private function corrigerTousLesComptesSequences($dryRun, $debug)
    {
        $comptes = Compte::all();
        
        if ($comptes->isEmpty()) {
            $this->error("Aucun compte trouvé");
            return 1;
        }
        
        $this->info("Correction de " . $comptes->count() . " comptes");
        $this->newLine();
        
        $totalCorrections = 0;
        $totalSequences = 0;
        $totalErreurs = 0;
        
        foreach ($comptes as $compte) {
            try {
                if ($debug) {
                    $this->info("Traitement du compte: {$compte->numero_compte}");
                }
                
                $mouvements = Mouvement::where('compte_id', $compte->id)
                    ->orWhere('numero_compte', $compte->numero_compte)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();
                
                if ($mouvements->isEmpty()) {
                    continue;
                }
                
                $soldeCourant = 0;
                $correctionsCompte = 0;
                $sequencesCompte = 0;
                
                // Détecter les séquences
                $lastTimestamp = null;
                $sequenceCount = 0;
                
                foreach ($mouvements as $mouvement) {
                    $timestamp = $mouvement->created_at->format('Y-m-d H:i:s');
                    
                    if ($timestamp === $lastTimestamp) {
                        $sequenceCount++;
                    } else {
                        if ($sequenceCount > 1) {
                            $sequencesCompte++;
                        }
                        $sequenceCount = 1;
                        $lastTimestamp = $timestamp;
                    }
                }
                
                if ($sequenceCount > 1) {
                    $sequencesCompte++;
                }
                
                // Recalculer tout
                $soldeCourant = 0;
                
                foreach ($mouvements as $mouvement) {
                    $soldeAvant = $soldeCourant;
                    
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
                    
                    if (abs($mouvement->solde_avant - $soldeAvant) > 0.01 || 
                        abs($mouvement->solde_apres - $soldeApres) > 0.01) {
                        
                        if (!$dryRun) {
                            $mouvement->solde_avant = $soldeAvant;
                            $mouvement->solde_apres = $soldeApres;
                            $mouvement->save();
                        }
                        
                        $correctionsCompte++;
                    }
                }
                
                // Mettre à jour le solde du compte
                $dernierSolde = $soldeCourant;
                
                if (!$dryRun && abs($dernierSolde - $compte->solde) > 0.01) {
                    $compte->solde = $dernierSolde;
                    $compte->save();
                }
                
                if ($sequencesCompte > 0) {
                    $totalSequences += $sequencesCompte;
                    $totalCorrections += $correctionsCompte;
                    
                    if ($debug) {
                        $this->info("  ⚠️  {$sequencesCompte} séquences, {$correctionsCompte} corrections");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("  ❌ Erreur compte {$compte->id}: " . $e->getMessage());
                $totalErreurs++;
                Log::error("Erreur correction séquences compte {$compte->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->newLine();
        $this->info("=== RÉCAPITULATIF FINAL ===");
        $this->info("Total comptes traités: " . $comptes->count());
        $this->info("Total séquences problématiques: {$totalSequences}");
        $this->info("Total corrections: {$totalCorrections}");
        $this->info("Total erreurs: {$totalErreurs}");
        $this->info("Mode: " . ($dryRun ? 'SIMULATION' : 'CORRECTIONS APPLIQUÉES'));
        
        if ($totalCorrections > 0 && $dryRun) {
            $this->info("\n⚠️  Exécutez sans --dry-run pour corriger les {$totalCorrections} incohérences.");
        }
        
        return 0;
    }
}