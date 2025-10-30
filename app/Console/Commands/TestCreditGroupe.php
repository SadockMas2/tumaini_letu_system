<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CreditGroupe;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestCreditGroupe extends Command
{
    protected $signature = 'test:credit-groupe {credit_groupe_id}';
    protected $description = 'Tester l\'approbation d\'un crédit groupe';

    public function handle()
    {
        $creditGroupeId = $this->argument('credit_groupe_id');
        
        $this->info("=== TEST CRÉDIT GROUPE ID: {$creditGroupeId} ===");

        try {
            // 1. Vérifier si le crédit groupe existe
            $credit = CreditGroupe::find($creditGroupeId);
            if (!$credit) {
                $this->error("❌ Crédit groupe non trouvé");
                return;
            }

            $this->info("✅ Crédit groupe trouvé:");
            $this->info("   - Montant demandé: " . $credit->montant_demande);
            $this->info("   - Compte ID: " . $credit->compte_id);
            $this->info("   - Statut: " . $credit->statut_demande);

            // 2. Vérifier le compte associé
            $compte = $credit->compte;
            if (!$compte) {
                $this->error("❌ Compte non trouvé");
                return;
            }

            $this->info("✅ Compte trouvé:");
            $this->info("   - Numéro: " . $compte->numero_compte);
            $this->info("   - Solde: " . $compte->solde);
            $this->info("   - Groupe Solidaire ID: " . $compte->groupe_solidaire_id);

            // 3. Vérifier les membres
            $membres = $credit->membres;
            $this->info("✅ Membres trouvés: " . $membres->count());

            foreach ($membres as $membre) {
                $this->info("   - Membre: {$membre->nom} {$membre->prenom} (ID: {$membre->id})");
                $this->info("     Compte: {$membre->numero_compte}, Solde: {$membre->solde}");
            }

            // 4. Tester la répartition
            $montantsTest = [];
            foreach ($membres as $membre) {
                $montantsTest[$membre->id] = 100; // Montant test
            }

            $this->info("🧪 Test de répartition...");
            $repartition = CreditGroupe::calculerRepartitionAvecMontants($montantsTest, 300);
            $this->info("✅ Répartition test réussie");

            // 5. Tester la création de crédits individuels
            $this->info("🧪 Test création crédits individuels...");
            
            DB::beginTransaction();
            try {
                // Simuler l'approbation
                $credit->update([
                    'montants_membres' => $montantsTest,
                    'montant_accorde' => 300,
                ]);

                $credit->creerCreditsIndividuels();
                $this->info("✅ Création crédits individuels réussie");
                
                DB::rollBack(); // Annuler pour ne pas persister les données
                $this->info("🎉 Tous les tests sont passés avec succès!");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ Erreur lors du test création crédits: " . $e->getMessage());
                $this->error("Fichier: " . $e->getFile() . " Ligne: " . $e->getLine());
            }

        } catch (\Exception $e) {
            $this->error("❌ Erreur générale: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . " Ligne: " . $e->getLine());
        }
    }
}