<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CreditGroupe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestCreditComplet extends Command
{
    protected $signature = 'test:credit-complet {id=1}';
    protected $description = 'Test complet d\'approbation de crédit groupe';

    public function handle()
    {
        $creditGroupeId = $this->argument('id');
        
        $this->info("🎯 === TEST COMPLET CRÉDIT GROUPE ID: {$creditGroupeId} ===");

        try {
            $credit = CreditGroupe::find($creditGroupeId);
            
            if (!$credit) {
                $this->error("❌ Crédit groupe non trouvé");
                return;
            }

            $this->info("✅ Crédit groupe trouvé:");
            $this->info("   - ID: " . $credit->id);
            $this->info("   - Montant demandé: " . $credit->montant_demande);
            $this->info("   - Compte ID: " . $credit->compte_id);
            $this->info("   - Statut: " . $credit->statut_demande);

            // Données de test
            $montantsMembres = [
                2 => 200, // Louise Martin
                3 => 300  // KWABO Alain
            ];
            
            $montantTotalGroupe = 500;
            
            $this->info("📊 Données de test:");
            $this->info("   - Montant total groupe: " . $montantTotalGroupe);
            $this->info("   - Répartition: " . json_encode($montantsMembres));

            // Début transaction
            DB::beginTransaction();
            
            try {
                $this->info("🔄 Début de la transaction...");

                // Étape 1: Mise à jour du crédit groupe
                $this->info("📝 Mise à jour du crédit groupe...");
                
                $credit->update([
                    'montant_accorde' => $montantTotalGroupe,
                    'montant_total' => $montantTotalGroupe * 1.225,
                    'frais_dossier' => 20,
                    'frais_alerte' => 4.5,
                    'frais_carnet' => 2.5,
                    'frais_adhesion' => 1,
                    'caution_totale' => 100,
                    'remboursement_hebdo_total' => ($montantTotalGroupe * 1.225) / 16,
                    'repartition_membres' => $montantsMembres,
                    'montants_membres' => $montantsMembres,
                    'statut_demande' => 'approuve',
                    'date_octroi' => now(),
                    'date_echeance' => now()->addMonths(4),
                ]);
                
                $this->info("✅ Crédit groupe mis à jour");

                // Étape 2: Vérification des membres
                $this->info("👥 Vérification des membres...");
                $membres = $credit->membres;
                $this->info("   Membres trouvés: " . $membres->count());
                
                foreach ($membres as $membre) {
                    $this->info("   - {$membre->nom} {$membre->prenom} (ID: {$membre->id})");
                }

                // Étape 3: Création des crédits individuels
                $this->info("💳 Création des crédits individuels...");
                $credit->creerCreditsIndividuels();
                $this->info("✅ Crédits individuels créés");

                // Vérification
                $creditsCrees = DB::table('credits')
                    ->where('credit_groupe_id', $credit->id)
                    ->get();
                    
                $this->info("📋 Crédits créés: " . $creditsCrees->count());
                foreach ($creditsCrees as $creditIndiv) {
                    $this->info("   - Crédit ID: {$creditIndiv->id}, Montant: {$creditIndiv->montant_accorde}");
                }

                // Annulation
                DB::rollBack();
                $this->info("🔄 Transaction annulée (test seulement)");

                $this->info("🎉 === TEST RÉUSSI ===");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ Erreur lors du test: " . $e->getMessage());
                $this->error("Fichier: " . $e->getFile() . " Ligne: " . $e->getLine());
                $this->error("Trace: " . $e->getTraceAsString());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur générale: " . $e->getMessage());
        }
        
        // Affichage des logs récents
        $this->info("📋 Derniers logs:");
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = file($logFile);
            $recentLogs = array_slice($logs, -20);
            foreach ($recentLogs as $log) {
                $this->line($log);
            }
        }
    }
}