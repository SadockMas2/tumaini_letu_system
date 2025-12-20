<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\MouvementHelper;

class RecalculSoldesCorrect extends Command
{
    protected $signature = 'recalcul:soldes-correct 
                           {--compte-id= : ID spécifique}
                           {--dry-run : Voir sans appliquer}
                           {--fix : Corriger aussi les mouvements}';
    
    protected $description = 'Recalcule les soldes selon les types de mouvement';
    
    public function handle()
    {
        $compteId = $this->option('compte-id');
        $dryRun = $this->option('dry-run');
        $fixMouvements = $this->option('fix');
        
        $this->info('🎯 RECALCUL DES SOLDES CORRECTEMENT');
        $this->info('=====================================');
        
        // 1. RÉCUPÉRER TOUS LES TYPES DE MOUVEMENT
        $typesMouvements = DB::table('mouvements')
            ->select('type_mouvement')
            ->distinct()
            ->pluck('type_mouvement')
            ->toArray();
        
        $this->info("\n📊 Types de mouvement trouvés : " . count($typesMouvements));
        
        // 2. ANALYSER CHAQUE TYPE
        $this->info("\n🔍 Analyse des types de mouvement :");
        $analyseTypes = [];
        
        foreach ($typesMouvements as $type) {
            $affichage = MouvementHelper::getTypeAffichage($type);
            $signe = MouvementHelper::getSigne($type);
            $traduction = MouvementHelper::traduireType($type);
            
            $analyseTypes[] = [
                'type' => $type,
                'affichage' => $affichage,
                'signe' => $signe,
                'traduction' => $traduction,
                'exemple_montant' => $this->getExempleMontant($type)
            ];
        }
        
        $this->table(
            ['Type', 'Affichage', 'Signe', 'Traduction', 'Exemple Montant'],
            $analyseTypes
        );
        
        // 3. POUR CHAQUE COMPTE, RECALCULER AVEC LA LOGIQUE
        $query = DB::table('comptes')
            ->select('id', 'numero_compte', 'solde');
            
        if ($compteId) {
            $query->where('id', $compteId);
        }
        
        $comptes = $query->get();
        
        $this->info("\n📈 Recalcul pour " . $comptes->count() . " comptes...");
        
        $comptesCorriges = [];
        $totalDifference = 0;
        
        foreach ($comptes as $compte) {
            // Récupérer tous les mouvements du compte
            $mouvements = DB::table('mouvements')
                ->where('compte_id', $compte->id)
                ->orderBy('date_mouvement', 'asc')
                ->orderBy('id', 'asc')
                ->get(['id', 'type_mouvement', 'montant', 'solde_avant', 'solde_apres']);
            
            if ($mouvements->isEmpty()) {
                continue;
            }
            
            // RECALCULER LE SOLDE FINAL
            $soldeCalcule = 0;
            $detailsCalcul = [];
            
            foreach ($mouvements as $mouvement) {
                $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
                $montantAbsolu = abs($mouvement->montant);
                
                switch ($typeAffichage) {
                    case 'depot':
                        // DÉPÔT : AJOUTER le montant
                        $soldeCalcule += $montantAbsolu;
                        $operation = "+ {$montantAbsolu}";
                        break;
                        
                    case 'retrait':
                        // RETRAIT : SOUSTRAIRE le montant
                        $soldeCalcule -= $montantAbsolu;
                        $operation = "- {$montantAbsolu}";
                        break;
                        
                    case 'neutre':
                        // NEUTRE : garder le montant tel quel (peut être positif ou négatif)
                        $soldeCalcule += $mouvement->montant;
                        $operation = $mouvement->montant >= 0 ? "+ {$mouvement->montant}" : "- " . abs($mouvement->montant);
                        break;
                        
                    default:
                        // AUTRE : utiliser le signe du montant
                        $soldeCalcule += $mouvement->montant;
                        $operation = $mouvement->montant >= 0 ? "+ {$mouvement->montant}" : "- " . abs($mouvement->montant);
                        break;
                }
                
                $detailsCalcul[] = [
                    'mouvement_id' => $mouvement->id,
                    'type' => $mouvement->type_mouvement,
                    'affichage' => $typeAffichage,
                    'montant' => $mouvement->montant,
                    'operation' => $operation,
                    'solde_intermediaire' => $soldeCalcule
                ];
            }
            
            $soldeCalcule = round($soldeCalcule, 2);
            $soldeActuel = round($compte->solde, 2);
            $difference = $soldeCalcule - $soldeActuel;
            
            if (abs($difference) > 0.01) {
                $comptesCorriges[] = [
                    'compte' => $compte,
                    'solde_actuel' => $soldeActuel,
                    'solde_calcule' => $soldeCalcule,
                    'difference' => $difference,
                    'mouvements' => $mouvements->count(),
                    'details' => $detailsCalcul
                ];
                
                $totalDifference += abs($difference);
                
                if (!$dryRun) {
                    // Mettre à jour le solde du compte
                    DB::table('comptes')
                        ->where('id', $compte->id)
                        ->update(['solde' => $soldeCalcule]);
                    
                    // Mettre à jour les soldes des mouvements si demandé
                    if ($fixMouvements) {
                        $this->corrigerSoldesMouvements($compte->id, $detailsCalcul);
                    }
                }
            }
        }
        
        // 4. AFFICHER LES RÉSULTATS
        if (!empty($comptesCorriges)) {
            $this->info("\n🚨 " . count($comptesCorriges) . " COMPTES À CORRIGER");
            $this->info("💰 Différence totale : " . number_format($totalDifference, 2));
            
            foreach ($comptesCorriges as $index => $correction) {
                $compte = $correction['compte'];
                
                $this->info("\n--- COMPTE #{$compte->id} : {$compte->numero_compte} ---");
                $this->line("Solde actuel : " . number_format($correction['solde_actuel'], 2));
                $this->line("Solde calculé : " . number_format($correction['solde_calcule'], 2));
                $this->line("Différence : " . number_format($correction['difference'], 2));
                $this->line("Mouvements : " . $correction['mouvements']);
                
                // Afficher les 5 premiers détails
                $this->info("\nDétails du calcul (5 premiers) :");
                $this->table(
                    ['ID', 'Type', 'Affichage', 'Montant', 'Opération', 'Solde'],
                    array_slice(array_map(function($detail) {
                        return [
                            $detail['mouvement_id'],
                            substr($detail['type'], 0, 20),
                            $detail['affichage'],
                            number_format($detail['montant'], 2),
                            $detail['operation'],
                            number_format($detail['solde_intermediaire'], 2)
                        ];
                    }, $correction['details']), 0, 5)
                );
                
                if (count($correction['details']) > 5) {
                    $this->line("... et " . (count($correction['details']) - 5) . " autres mouvements");
                }
            }
            
            if ($dryRun) {
                $this->warn("\n🔍 MODE DRY RUN - AUCUNE MODIFICATION APPLIQUÉE");
                $this->info("Pour appliquer : php artisan recalcul:soldes-correct");
                $this->info("Pour appliquer + corriger mouvements : php artisan recalcul:soldes-correct --fix");
            } else {
                $this->info("\n✅ " . count($comptesCorriges) . " comptes corrigés !");
                
                if ($fixMouvements) {
                    $this->info("✅ Soldes des mouvements également corrigés");
                }
            }
        } else {
            $this->info("\n✅ TOUS LES SOLDES SONT DÉJÀ CORRECTS !");
        }
        
        return 0;
    }
    
    private function getExempleMontant($typeMouvement)
    {
        $exemple = DB::table('mouvements')
            ->where('type_mouvement', $typeMouvement)
            ->select('montant')
            ->first();
            
        return $exemple ? number_format($exemple->montant, 2) : 'N/A';
    }
    
    private function corrigerSoldesMouvements($compteId, $detailsCalcul)
    {
        $soldeCourant = 0;
        
        foreach ($detailsCalcul as $detail) {
            $soldeAvant = $soldeCourant;
            
            // Recalculer le solde après
            $operation = $detail['operation'];
            if (str_starts_with($operation, '+')) {
                $montant = (float) str_replace('+ ', '', $operation);
                $soldeCourant += $montant;
            } else {
                $montant = (float) str_replace('- ', '', $operation);
                $soldeCourant -= $montant;
            }
            
            // Mettre à jour le mouvement
            DB::table('mouvements')
                ->where('id', $detail['mouvement_id'])
                ->update([
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $soldeCourant,
                    'updated_at' => now()
                ]);
        }
        
        $this->line("  📝 Compte #{$compteId} : " . count($detailsCalcul) . " mouvements corrigés");
    }
}