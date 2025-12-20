<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Credit;
use App\Models\Compte;
use App\Models\Mouvement;

class AddMissingCreditMouvementsWithDate extends Command
{
    protected $signature = 'add:missing-credit-mouvements-date 
                           {--dry-run : Voir ce qui sera ajouté sans appliquer}
                           {--compte-id= : Filtrer par compte spécifique}
                           {--credit-id= : Filtrer par crédit spécifique}
                           {--start-date= : Date de début (format: YYYY-MM-DD)}
                           {--end-date= : Date de fin (format: YYYY-MM-DD)}
                           {--date-only : N\'afficher que les dates sans les autres détails}';
    
    protected $description = 'Ajoute les mouvements manquants d\'octroi de crédit avec la date d\'octroi comme date de création';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $compteId = $this->option('compte-id');
        $creditId = $this->option('credit-id');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $dateOnly = $this->option('date-only');
        
        $this->info('🔍 Recherche des crédits individuels sans mouvement d\'octroi...');
        
        // Construire la requête pour trouver les crédits sans mouvement d'octroi
        $query = Credit::where('statut_demande', 'approuve')
            ->where('type_credit', 'individuel')
            ->whereNotNull('montant_accorde')
            ->where('montant_accorde', '>', 0)
            ->whereDoesntHave('compte.mouvements', function($q) {
                $q->where('type_mouvement', 'credit_octroye')
                  ->whereColumn('mouvements.compte_id', 'credits.compte_id');
            });
        
        if ($compteId) {
            $query->where('compte_id', $compteId);
        }
        
        if ($creditId) {
            $query->where('id', $creditId);
        }
        
        if ($startDate) {
            $query->where('date_octroi', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date_octroi', '<=', $endDate);
        }
        
        $credits = $query->with('compte')->orderBy('date_octroi')->get();
        
        $this->info("📊 Trouvés : {$credits->count()} crédits sans mouvement d'octroi");
        
        if ($credits->isEmpty()) {
            $this->info('✅ Tous les crédits ont déjà leur mouvement d\'octroi.');
            return 0;
        }
        
        // Afficher un résumé
        if (!$dateOnly) {
            $this->info("\n📋 Résumé des crédits à traiter :");
            $this->table(
                ['ID Crédit', 'Compte', 'Montant accordé', 'Date octroi', 'Statut'],
                $credits->map(function($credit) {
                    return [
                        $credit->id,
                        $credit->compte->numero_compte ?? 'N/A',
                        $credit->montant_accorde . ' USD',
                        $credit->date_octroi?->format('d/m/Y H:i:s') ?? 'N/A',
                        $credit->statut_demande
                    ];
                })->toArray()
            );
        } else {
            // Afficher seulement les dates
            $this->info("\n📅 Dates d\'octroi des crédits à traiter :");
            $dates = $credits->map(function($credit) {
                return $credit->date_octroi?->format('Y-m-d H:i:s') ?? $credit->created_at->format('Y-m-d H:i:s');
            })->unique()->sort();
            
            foreach ($dates as $date) {
                $count = $credits->filter(function($credit) use ($date) {
                    $creditDate = $credit->date_octroi?->format('Y-m-d H:i:s') ?? $credit->created_at->format('Y-m-d H:i:s');
                    return $creditDate === $date;
                })->count();
                
                $this->line("  {$date} : {$count} crédit(s)");
            }
        }
        
        if ($dryRun) {
            $this->warn('🔍 Mode DRY RUN - Aucun mouvement ne sera ajouté');
            return 0;
        }
        
        $this->warn('⚠️  Cette opération va ajouter des mouvements d\'octroi de crédit manquants.');
        $this->warn('⚠️  Les mouvements auront la date d\'octroi comme date de création et de mise à jour.');
        
        if (!$this->confirm('Êtes-vous sûr de vouloir continuer ?')) {
            $this->error('❌ Opération annulée.');
            return 1;
        }
        
        $this->info('🔄 Ajout des mouvements manquants avec dates d\'octroi...');
        
        $addedCount = 0;
        $errors = [];
        
        foreach ($credits as $credit) {
            try {
                $compte = $credit->compte;
                if (!$compte) {
                    $errors[] = "Crédit #{$credit->id}: Compte non trouvé";
                    $this->error("❌ Compte non trouvé pour le crédit #{$credit->id}");
                    continue;
                }
                
                // Déterminer la date d'octroi
                $dateOctroi = $credit->date_octroi ?? $credit->created_at;
                
                // 1. Trouver le solde avant l'octroi
                // Chercher le dernier mouvement avant la date d'octroi
                $lastMouvement = Mouvement::where('compte_id', $compte->id)
                    ->where('date_mouvement', '<', $dateOctroi)
                    ->orderBy('date_mouvement', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                
                // 2. Trouver le mouvement de frais correspondant à ce crédit
                $mouvementFrais = Mouvement::where('compte_id', $compte->id)
                    ->where('type_mouvement', 'frais_payes_credit')
                    ->where(function($q) use ($credit) {
                        $q->where('description', 'like', '%Crédit #' . $credit->id . '%')
                          ->orWhere('reference', 'like', '%FRAIS-CREDIT-' . $credit->id . '%');
                    })
                    ->first();
                
                $soldeAvant = 0;
                
                if ($lastMouvement) {
                    $soldeAvant = $lastMouvement->solde_apres;
                } elseif ($mouvementFrais) {
                    // Si pas de dernier mouvement mais il y a des frais, prendre le solde après les frais
                    $soldeAvant = $mouvementFrais->solde_apres;
                } else {
                    // Sinon, chercher le solde minimum dans l'historique
                    $minSolde = Mouvement::where('compte_id', $compte->id)
                        ->where('date_mouvement', '<', $dateOctroi)
                        ->min('solde_apres');
                    
                    $soldeAvant = $minSolde ?? 0;
                }
                
                $soldeApres = $soldeAvant + $credit->montant_accorde;
                
                // 3. Utiliser DB::table() pour insérer avec les dates spécifiques
                DB::table('mouvements')->insert([
                    'compte_id' => $compte->id,
                    'type_mouvement' => 'credit_octroye',
                    'type' => 'depot',
                    'montant' => $credit->montant_accorde,
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $soldeApres,
                    'description' => "Octroi de crédit individuel #{$credit->id} - Montant: {$credit->montant_accorde} USD",
                    'reference' => 'CREDIT-' . $credit->id,
                    'date_mouvement' => $dateOctroi,
                    'nom_deposant' => 'TUMAINI LETU Finances',
                    'created_at' => $dateOctroi, // ✅ Date d'octroi comme date de création
                    'updated_at' => $dateOctroi, // ✅ Date d'octroi comme date de mise à jour
                ]);
                
                $addedCount++;
                $this->line("✅ Crédit #{$credit->id} ({$dateOctroi->format('d/m/Y H:i')}) : Mouvement d'octroi ajouté ({$credit->montant_accorde} USD)");
                
                // 4. Vérifier s'il y a un mouvement de frais à corriger aussi
                if ($mouvementFrais) {
                    // Si le mouvement de frais n'a pas la même date, le corriger aussi
                    if ($mouvementFrais->date_mouvement != $dateOctroi) {
                        DB::table('mouvements')
                            ->where('id', $mouvementFrais->id)
                            ->update([
                                'date_mouvement' => $dateOctroi,
                                'created_at' => $dateOctroi,
                                'updated_at' => $dateOctroi
                            ]);
                        
                        $this->line("   🔄 Date du mouvement de frais corrigée : {$mouvementFrais->date_mouvement->format('d/m/Y H:i')} → {$dateOctroi->format('d/m/Y H:i')}");
                    }
                }
                
            } catch (\Exception $e) {
                $errors[] = "Crédit #{$credit->id}: " . $e->getMessage();
                $this->error("❌ Erreur crédit #{$credit->id}: " . $e->getMessage());
                Log::error("Erreur ajout mouvement crédit #{$credit->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Afficher le résumé
        $this->info("\n🎯 RÉSULTAT :");
        $this->info("  - Mouvements ajoutés : {$addedCount}/{$credits->count()}");
        $this->info("  - Dates utilisées : date d'octroi des crédits");
        
        if (!empty($errors)) {
            $this->error("\n❌ Erreurs rencontrées :");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }
        
        // Vérifier l'ordre chronologique
        $this->info("\n🔍 Vérification de l'ordre chronologique...");
        $this->verifierOrdreChronologique($credits->pluck('compte_id')->unique());
        
        Log::info('Ajout mouvements octroi de crédit avec dates', [
            'mouvements_ajoutes' => $addedCount,
            'total_credits' => $credits->count(),
            'erreurs' => count($errors)
        ]);
        
        $this->info("\n✅ Opération terminée !");
        
        return 0;
    }
    
    /**
     * Vérifie l'ordre chronologique des mouvements après l'ajout
     */
    private function verifierOrdreChronologique($compteIds)
    {
        $comptesAvecProblemes = 0;
        
        foreach ($compteIds as $compteId) {
            // Récupérer tous les mouvements du compte dans l'ordre chronologique
            $mouvements = Mouvement::where('compte_id', $compteId)
                ->orderBy('date_mouvement')
                ->orderBy('id')
                ->get(['id', 'type_mouvement', 'montant', 'solde_avant', 'solde_apres', 'date_mouvement', 'created_at']);
            
            if ($mouvements->count() < 2) {
                continue;
            }
            
            // Vérifier l'ordre chronologique
            $problemes = [];
            $dernierMouvement = null;
            
            foreach ($mouvements as $mouvement) {
                if ($dernierMouvement && $mouvement->date_mouvement < $dernierMouvement->date_mouvement) {
                    $problemes[] = [
                        'mouvement_id' => $mouvement->id,
                        'date_mouvement' => $mouvement->date_mouvement->format('Y-m-d H:i:s'),
                        'type' => $mouvement->type_mouvement,
                        'precedent_id' => $dernierMouvement->id,
                        'precedent_date' => $dernierMouvement->date_mouvement->format('Y-m-d H:i:s')
                    ];
                }
                $dernierMouvement = $mouvement;
            }
            
            if (!empty($problemes)) {
                $comptesAvecProblemes++;
                $compte = Compte::find($compteId);
                $this->warn("⚠️  Compte {$compte->numero_compte} : " . count($problemes) . " problèmes d'ordre chronologique");
                
                foreach ($problemes as $probleme) {
                    $this->line("   - Mouvement #{$probleme['mouvement_id']} ({$probleme['type']}) à {$probleme['date_mouvement']} est avant le précédent #{$probleme['precedent_id']} à {$probleme['precedent_date']}");
                }
            }
        }
        
        if ($comptesAvecProblemes > 0) {
            $this->warn("\n⚠️  Total des comptes avec problèmes d'ordre chronologique : {$comptesAvecProblemes}");
            $this->info("💡 Il est recommandé de réorganiser les mouvements par date.");
        } else {
            $this->info("✅ Tous les mouvements sont dans l'ordre chronologique correct !");
        }
    }
}