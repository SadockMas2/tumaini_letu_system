<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Credit;
use App\Models\Compte;
use App\Models\Mouvement;

class AddMissingCreditMouvements extends Command
{
    protected $signature = 'add:missing-credit-mouvements 
                           {--dry-run : Voir ce qui sera ajouté sans appliquer}
                           {--compte-id= : Filtrer par compte spécifique}
                           {--credit-id= : Filtrer par crédit spécifique}
                           {--start-date= : Date de début (format: YYYY-MM-DD)}
                           {--end-date= : Date de fin (format: YYYY-MM-DD)}';
    
    protected $description = 'Ajoute les mouvements manquants d\'octroi de crédit pour les crédits individuels';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $compteId = $this->option('compte-id');
        $creditId = $this->option('credit-id');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        
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
        
        $credits = $query->with('compte')->get();
        
        $this->info("📊 Trouvés : {$credits->count()} crédits sans mouvement d'octroi");
        
        if ($credits->isEmpty()) {
            $this->info('✅ Tous les crédits ont déjà leur mouvement d\'octroi.');
            return 0;
        }
        
        // Afficher un résumé
        $this->info("\n📋 Résumé des crédits à traiter :");
        $this->table(
            ['ID Crédit', 'Compte', 'Montant accordé', 'Date octroi', 'Statut'],
            $credits->map(function($credit) {
                return [
                    $credit->id,
                    $credit->compte->numero_compte ?? 'N/A',
                    $credit->montant_accorde . ' USD',
                    $credit->date_octroi?->format('d/m/Y') ?? 'N/A',
                    $credit->statut_demande
                ];
            })->toArray()
        );
        
        if ($dryRun) {
            $this->warn('🔍 Mode DRY RUN - Aucun mouvement ne sera ajouté');
            return 0;
        }
        
        $this->warn('⚠️  Cette opération va ajouter des mouvements d\'octroi de crédit manquants.');
        
        if (!$this->confirm('Êtes-vous sûr de vouloir continuer ?')) {
            $this->error('❌ Opération annulée.');
            return 1;
        }
        
        $this->info('🔄 Ajout des mouvements manquants...');
        
        $addedCount = 0;
        $errors = [];
        
        foreach ($credits as $credit) {
            try {
                $compte = $credit->compte;
                if (!$compte) {
                    $this->error("❌ Compte non trouvé pour le crédit #{$credit->id}");
                    continue;
                }
                
                // 1. Trouver le solde avant l'octroi
                // Chercher le dernier mouvement avant la date d'octroi
                $lastMouvement = Mouvement::where('compte_id', $compte->id)
                    ->where('date_mouvement', '<', $credit->date_octroi ?? $credit->created_at)
                    ->orderBy('date_mouvement', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                
                $soldeAvant = $lastMouvement ? $lastMouvement->solde_apres : 0;
                $soldeApres = $soldeAvant + $credit->montant_accorde;
                
                // 2. Créer le mouvement d'octroi de crédit
                Mouvement::create([
                    'compte_id' => $compte->id,
                    'type_mouvement' => 'credit_octroye',
                    'type' => 'depot',
                    'montant' => $credit->montant_accorde,
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $soldeApres,
                    'description' => "Octroi de crédit individuel #{$credit->id} - Montant: {$credit->montant_accorde} USD",
                    'reference' => 'CREDIT-' . $credit->id,
                    'date_mouvement' => $credit->date_octroi ?? $credit->created_at,
                    'nom_deposant' => 'TUMAINI LETU Finances',
                    'created_at' => $credit->date_octroi ?? $credit->created_at,
                    'updated_at' => $credit->date_octroi ?? $credit->created_at,
                ]);
                
                $addedCount++;
                $this->line("✅ Crédit #{$credit->id}: Mouvement d'octroi ajouté ({$credit->montant_accorde} USD)");
                
            } catch (\Exception $e) {
                $errors[] = "Crédit #{$credit->id}: " . $e->getMessage();
                $this->error("❌ Erreur crédit #{$credit->id}: " . $e->getMessage());
            }
        }
        
        // Afficher le résumé
        $this->info("\n🎯 RÉSULTAT :");
        $this->info("  - Mouvements ajoutés : {$addedCount}/{$credits->count()}");
        
        if (!empty($errors)) {
            $this->error("\n❌ Erreurs rencontrées :");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }
        
        // Maintenant, vérifier les incohérences après l'ajout
        $this->info("\n🔍 Vérification des incohérences après correction...");
        $this->verifierIncoherencesApresCorrection($credits->pluck('compte_id')->unique());
        
        Log::info('Ajout mouvements octroi de crédit', [
            'mouvements_ajoutes' => $addedCount,
            'total_credits' => $credits->count(),
            'erreurs' => count($errors)
        ]);
        
        $this->info("\n✅ Opération terminée !");
        
        return 0;
    }
    
    /**
     * Vérifie les incohérences après l'ajout des mouvements
     */
    private function verifierIncoherencesApresCorrection($compteIds)
    {
        $incoherences = 0;
        
        foreach ($compteIds as $compteId) {
            // Récupérer tous les mouvements du compte
            $mouvements = Mouvement::where('compte_id', $compteId)
                ->orderBy('date_mouvement')
                ->orderBy('id')
                ->get();
            
            if ($mouvements->isEmpty()) {
                continue;
            }
            
            // Vérifier la cohérence des soldes
            $soldeAttendu = 0;
            $incoherents = [];
            
            foreach ($mouvements as $index => $mouvement) {
                // Calculer le solde attendu après ce mouvement
                $soldeAttenduAvant = $soldeAttendu;
                $soldeAttendu += $mouvement->montant;
                
                // Vérifier l'incohérence
                if ($index > 0 && abs($mouvement->solde_avant - $soldeAttenduAvant) > 0.01) {
                    $incoherents[] = [
                        'mouvement_id' => $mouvement->id,
                        'solde_avant_enregistre' => $mouvement->solde_avant,
                        'solde_avant_calcule' => $soldeAttenduAvant,
                        'difference' => $mouvement->solde_avant - $soldeAttenduAvant
                    ];
                }
                
                if (abs($mouvement->solde_apres - $soldeAttendu) > 0.01) {
                    $incoherents[] = [
                        'mouvement_id' => $mouvement->id,
                        'solde_apres_enregistre' => $mouvement->solde_apres,
                        'solde_apres_calcule' => $soldeAttendu,
                        'difference' => $mouvement->solde_apres - $soldeAttendu
                    ];
                }
            }
            
            if (!empty($incoherents)) {
                $incoherences += count($incoherents);
                $compte = Compte::find($compteId);
                $this->warn("⚠️  Compte {$compte->numero_compte}: " . count($incoherents) . " incohérences détectées");
            }
        }
        
        if ($incoherences > 0) {
            $this->warn("\n⚠️  Total des incohérences après correction : {$incoherences}");
            $this->info("💡 Exécutez `php artisan fix:mouvement-inconsistencies` pour corriger ces incohérences.");
        } else {
            $this->info("✅ Aucune incohérence détectée après correction !");
        }
    }
}