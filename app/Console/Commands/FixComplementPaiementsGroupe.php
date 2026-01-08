<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mouvement;
use App\Models\PaiementCredit;
use App\Models\CreditGroupe;
use App\Models\Compte;
use App\Enums\TypePaiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FixComplementPaiementsGroupe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paiements:fix-complements
                            {--dry-run : Exécuter sans effectuer de modifications}
                            {--limit=100 : Limiter le nombre de corrections}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige les paiements groupe où les compléments ne sont pas enregistrés dans paiement_credits';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info("🔍 Recherche des mouvements 'complement_paiement_groupe' sans paiement associé...");

        // 1. Trouver tous les mouvements de complément
        $mouvements = Mouvement::where('type_mouvement', 'complement_paiement_groupe')
            ->orderBy('date_mouvement', 'asc')
            ->limit($limit)
            ->get();

        $this->info("📊 Trouvé {$mouvements->count()} mouvements de complément à vérifier");

        $corrections = 0;
        $erreurs = 0;

        foreach ($mouvements as $mouvement) {
            try {
                $this->line("---");
                $this->info("📝 Traitement mouvement ID: {$mouvement->id}");
                $this->line("   Compte: {$mouvement->compte_id}");
                $this->line("   Montant: {$mouvement->montant} USD");
                $this->line("   Date: {$mouvement->date_mouvement}");
                $this->line("   Référence: {$mouvement->reference}");

                // 2. Extraire l'ID du groupe depuis la référence
                $groupeId = $this->extraireGroupeIdDeReference($mouvement->reference);
                
                if (!$groupeId) {
                    $this->warn("   ⚠️ Impossible d'extraire l'ID du groupe depuis la référence");
                    continue;
                }

                $this->line("   Groupe ID extrait: {$groupeId}");

                // 3. Vérifier si un paiement existe déjà pour ce groupe à cette date
                // CORRECTION ICI : Convertir la chaîne en objet Carbon d'abord
                $dateMouvement = Carbon::parse($mouvement->date_mouvement);
                $datePaiement = $dateMouvement->format('Y-m-d');
                
                $paiementExiste = PaiementCredit::where('credit_groupe_id', $groupeId)
                    ->whereDate('date_paiement', $datePaiement)
                    ->where('type_paiement', TypePaiement::GROUPE->value)
                    ->exists();

                if ($paiementExiste) {
                    $this->info("   ✅ Paiement déjà enregistré pour ce groupe à cette date");
                    continue;
                }

                // 4. Récupérer le groupe
                $creditGroupe = CreditGroupe::find($groupeId);
                
                if (!$creditGroupe) {
                    $this->error("   ❌ Groupe ID {$groupeId} non trouvé");
                    $erreurs++;
                    continue;
                }

                // 5. Récupérer le compte du groupe
                $compteGroupe = $creditGroupe->compte;
                
                if (!$compteGroupe) {
                    $this->error("   ❌ Compte groupe non trouvé pour groupe ID {$groupeId}");
                    $erreurs++;
                    continue;
                }

                // 6. Trouver tous les mouvements de complément pour ce groupe à cette date
                $mouvementsGroupeDate = Mouvement::where('type_mouvement', 'complement_paiement_groupe')
                    ->whereDate('date_mouvement', $datePaiement)
                    ->where('reference', 'LIKE', "%GRP-{$groupeId}%")
                    ->get();

                $this->line("   📊 {$mouvementsGroupeDate->count()} mouvements trouvés pour ce groupe à cette date");

                // 7. Calculer le montant total remboursé
                $montantTotalRembourse = $mouvementsGroupeDate->sum('montant');
                
                // 8. Calculer la répartition capital/intérêts
                $repartition = $this->calculerRepartitionGroupe($creditGroupe, $montantTotalRembourse);

                // 9. Créer le paiement manquant
                if (!$dryRun) {
                    $paiement = PaiementCredit::create([
                        'credit_id' => null,
                        'credit_groupe_id' => $groupeId,
                        'compte_id' => $compteGroupe->id,
                        'montant_paye' => $montantTotalRembourse,
                        'date_paiement' => $dateMouvement, // Utiliser l'objet Carbon
                        'type_paiement' => TypePaiement::GROUPE->value,
                        'reference' => 'CORRECTION-GRP-' . $groupeId . '-' . now()->format('YmdHis'),
                        'statut' => 'complet',
                        'capital_rembourse' => $repartition['capital'],
                        'interets_payes' => $repartition['interets'],
                        'created_at' => $mouvement->created_at ?? now(),
                        'updated_at' => now(),
                    ]);

                    $this->info("   ✅ Paiement créé - ID: {$paiement->id}");
                    $this->line("      Montant: {$paiement->montant_paye} USD");
                    $this->line("      Capital: {$paiement->capital_rembourse} USD");
                    $this->line("      Intérêts: {$paiement->interets_payes} USD");

                    // 10. Mettre à jour l'échéancier
                    $this->mettreAJourEcheancier($creditGroupe, $paiement);

                    // 11. Générer les écritures comptables
                    $this->genererEcritureComptable($creditGroupe, $paiement, $repartition);

                    $corrections++;
                } else {
                    $this->info("   🔍 [DRY RUN] Paiement à créer pour {$montantTotalRembourse} USD");
                    $this->line("      Capital: {$repartition['capital']} USD");
                    $this->line("      Intérêts: {$repartition['interets']} USD");
                    $corrections++;
                }

            } catch (\Exception $e) {
                $this->error("   ❌ Erreur: " . $e->getMessage());
                Log::error('Erreur correction paiement groupe', [
                    'mouvement_id' => $mouvement->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $erreurs++;
            }
        }

        // Résumé
        $this->line("=========================================");
        
        if ($dryRun) {
            $this->info("📋 **RÉSUMÉ DRY RUN**");
            $this->info("   Corrections nécessaires: {$corrections}");
        } else {
            $this->info("📋 **RÉSUMÉ DES CORRECTIONS**");
            $this->info("   Corrections effectuées: {$corrections}");
        }
        
        $this->info("   Erreurs: {$erreurs}");
        $this->info("   Total mouvements traités: {$mouvements->count()}");

        if (!$dryRun && $corrections > 0) {
            $this->info("✅ Correction terminée avec succès!");
        } elseif ($dryRun) {
            $this->info("🔍 Dry run terminé. Aucune modification effectuée.");
        } else {
            $this->info("ℹ️ Aucune correction nécessaire.");
        }
    }

    /**
     * Extrait l'ID du groupe depuis la référence du mouvement
     */
    private function extraireGroupeIdDeReference($reference)
    {
        // Format: COMPL-MEMBRE-{membreId}-GRP-{groupeId}-{timestamp}
        if (preg_match('/GRP-(\d+)/', $reference, $matches)) {
            return $matches[1];
        }
        
        // Autres formats possibles
        if (preg_match('/GRP(\d+)/', $reference, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Calcule la répartition capital/intérêts pour un groupe
     */
    private function calculerRepartitionGroupe($creditGroupe, $montantPaiement)
    {
        // Calculer les parts hebdomadaires
        $capitalHebdomadaire = $creditGroupe->montant_accorde / 16;
        $interetHebdomadaire = ($creditGroupe->montant_total - $creditGroupe->montant_accorde) / 16;
        $montantHebdomadaireTotal = $capitalHebdomadaire + $interetHebdomadaire;
        
        // Si paiement complet ou supérieur au dû hebdomadaire
        if ($montantPaiement >= $montantHebdomadaireTotal) {
            return [
                'capital' => $capitalHebdomadaire,
                'interets' => $interetHebdomadaire,
                'excédent' => $montantPaiement - $montantHebdomadaireTotal
            ];
        }
        
        // Si paiement partiel : priorité aux intérêts
        $interetsAPayer = min($montantPaiement, $interetHebdomadaire);
        $capitalAPayer = max(0, $montantPaiement - $interetsAPayer);
        
        return [
            'capital' => $capitalAPayer,
            'interets' => $interetsAPayer,
            'excédent' => 0
        ];
    }

    /**
     * Met à jour l'échéancier après paiement
     */
    private function mettreAJourEcheancier($creditGroupe, $paiement)
    {
        try {
            // Trouver la prochaine échéance non payée avant la date du paiement
            $echeance = DB::table('echeanciers')
                ->where('credit_groupe_id', $creditGroupe->id)
                ->where('statut', 'a_venir')
                ->whereDate('date_echeance', '<=', $paiement->date_paiement)
                ->orderBy('semaine', 'asc')
                ->first();
                
            if ($echeance) {
                DB::table('echeanciers')
                    ->where('id', $echeance->id)
                    ->update([
                        'statut' => 'paye',
                        'date_paiement' => $paiement->date_paiement,
                        'montant_paye' => $paiement->montant_paye,
                        'updated_at' => now()
                    ]);
                
                $this->line("      📅 Échéance {$echeance->semaine} marquée comme payée");
            }
        } catch (\Exception $e) {
            $this->warn("      ⚠️ Impossible de mettre à jour l'échéancier: " . $e->getMessage());
        }
    }

    /**
     * Génère les écritures comptables
     */
    private function genererEcritureComptable($creditGroupe, $paiement, $repartition)
    {
        try {
            $journal = DB::table('journal_comptables')
                ->where('type_journal', 'banque')
                ->first();
                
            if (!$journal) {
                $this->warn("      ⚠️ Journal banque non trouvé");
                return;
            }

            $reference = 'CORRECTION-' . $paiement->reference;

            // 1. DÉBIT - Remboursement capital (compte 411100)
            if ($repartition['capital'] > 0) {
                DB::table('ecriture_comptables')->insert([
                    'journal_comptable_id' => $journal->id,
                    'reference_operation' => $reference,
                    'type_operation' => 'remboursement_capital_groupe',
                    'compte_number' => '411100',
                    'libelle' => "Correction - Remboursement capital crédit groupe - " . ($creditGroupe->compte->nom ?? 'Groupe'),
                    'montant_debit' => $repartition['capital'],
                    'montant_credit' => 0,
                    'date_ecriture' => $paiement->date_paiement,
                    'devise' => 'USD',
                    'statut' => 'comptabilise',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2. DÉBIT - Paiement intérêts (compte 411100)
            if ($repartition['interets'] > 0) {
                DB::table('ecriture_comptables')->insert([
                    'journal_comptable_id' => $journal->id,
                    'reference_operation' => $reference,
                    'type_operation' => 'paiement_interets_groupe',
                    'compte_number' => '411100',
                    'libelle' => "Correction - Paiement intérêts crédit groupe - " . ($creditGroupe->compte->nom ?? 'Groupe'),
                    'montant_debit' => $repartition['interets'],
                    'montant_credit' => 0,
                    'date_ecriture' => $paiement->date_paiement,
                    'devise' => 'USD',
                    'statut' => 'comptabilise',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 3. CRÉDIT - Recouvrement capital (compte 751100)
            if ($repartition['capital'] > 0) {
                DB::table('ecriture_comptables')->insert([
                    'journal_comptable_id' => $journal->id,
                    'reference_operation' => $reference,
                    'type_operation' => 'recouvrement_capital_groupe',
                    'compte_number' => '751100',
                    'libelle' => "Correction - Recouvrement capital crédit groupe - " . ($creditGroupe->compte->nom ?? 'Groupe'),
                    'montant_debit' => 0,
                    'montant_credit' => $repartition['capital'],
                    'date_ecriture' => $paiement->date_paiement,
                    'devise' => 'USD',
                    'statut' => 'comptabilise',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 4. CRÉDIT - Revenus intérêts (compte 758100)
            if ($repartition['interets'] > 0) {
                DB::table('ecriture_comptables')->insert([
                    'journal_comptable_id' => $journal->id,
                    'reference_operation' => $reference,
                    'type_operation' => 'revenus_interets_groupe',
                    'compte_number' => '758100',
                    'libelle' => "Correction - Revenus intérêts crédit groupe - " . ($creditGroupe->compte->nom ?? 'Groupe'),
                    'montant_debit' => 0,
                    'montant_credit' => $repartition['interets'],
                    'date_ecriture' => $paiement->date_paiement,
                    'devise' => 'USD',
                    'statut' => 'comptabilise',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->line("      📒 Écritures comptables générées");
            
        } catch (\Exception $e) {
            $this->warn("      ⚠️ Impossible de générer les écritures comptables: " . $e->getMessage());
        }
    }
}