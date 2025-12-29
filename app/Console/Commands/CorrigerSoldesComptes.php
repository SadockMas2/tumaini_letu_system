<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Compte;
use App\Models\Mouvement;
use App\Helpers\MouvementHelper;

class CorrigerSoldesComptes extends Command
{
    protected $signature = 'soldes:corriger 
        {compte? : Numéro du compte spécifique} 
        {--force : Appliquer la correction}
        {--test : Tester sans modifier}
        {--details : Afficher les détails du calcul}'; // ⬅️ Changé de --verbose à --details
    
    protected $description = 'Corrige les soldes des comptes en fonction des mouvements';

    public function handle()
    {
        $compteNum = $this->argument('compte');
        $force = $this->option('force');
        $test = $this->option('test');
        $details = $this->option('details'); // ⬅️ Changé ici
        
        if ($test) {
            $this->info("🔍 MODE TEST - Aucune modification ne sera appliquée");
            $force = false;
        }
        
        if ($compteNum) {
            $comptes = Compte::where('numero_compte', $compteNum)->get();
        } else {
            $comptes = Compte::all();
        }
        
        $this->info("📊 Vérification de " . $comptes->count() . " comptes...");
        
        $incoherences = [];
        $totalCorrection = 0;
        
        foreach ($comptes as $compte) {
            $this->line("▶️ Compte {$compte->numero_compte} ({$compte->nom})...");
            
            // Récupérer tous les mouvements
            $mouvements = Mouvement::where('compte_id', $compte->id)
                ->orWhere('numero_compte', $compte->numero_compte)
                ->orderBy('created_at', 'asc')
                ->get();
            
            // Recalculer le solde avec la nouvelle logique
            $soldeCalcule = 0;
            $historique = [];
            
            foreach ($mouvements as $mouvement) {
                $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
                $montant = floatval($mouvement->montant);
                $soldeAvant = $soldeCalcule;
                
                // Appliquer la logique
                if ($typeAffichage === 'depot') {
                    $soldeCalcule += $montant;
                    $operation = 'DEPOT (+)';
                } elseif ($typeAffichage === 'retrait') {
                    $soldeCalcule -= $montant;
                    $operation = 'RETRAIT (-)';
                } elseif ($typeAffichage === 'neutre') {
                    // Pour les neutres, on ignore si montant 0
                    if (abs($montant) > 0.01) {
                        $soldeCalcule += $montant;
                        $operation = 'NEUTRE';
                    } else {
                        $operation = 'NEUTRE (ignoré)';
                    }
                } else {
                    $soldeCalcule += $montant;
                    $operation = 'AUTRE';
                }
                
                // Garder l'historique pour le débogage
                $historique[] = [
                    'id' => $mouvement->id,
                    'type' => $mouvement->type_mouvement,
                    'affichage' => $typeAffichage,
                    'montant' => $montant,
                    'avant' => $soldeAvant,
                    'apres' => $soldeCalcule,
                    'operation' => $operation
                ];
            }
            
            $soldeCalcule = round($soldeCalcule, 2);
            $soldeBase = round(floatval($compte->solde), 2);
            $difference = round($soldeCalcule - $soldeBase, 2);
            
            if (abs($difference) > 0.01) {
                $incoherences[] = [
                    'compte' => $compte->numero_compte,
                    'nom' => $compte->nom,
                    'solde_base' => $soldeBase,
                    'solde_calcule' => $soldeCalcule,
                    'difference' => $difference,
                    'mouvements' => $mouvements->count(),
                    'historique' => $historique
                ];
                
                $this->warn("  ❌ Incohérence: {$soldeBase} → {$soldeCalcule} (diff: {$difference})");
                $totalCorrection += abs($difference);
                
                // Afficher les détails si demandé
                if ($details && !empty($historique)) {
                    $this->table(
                        ['ID', 'Type', 'Affichage', 'Montant', 'Avant', 'Après', 'Opération'],
                        array_map(function($h) {
                            return [
                                $h['id'],
                                $h['type'],
                                $h['affichage'],
                                number_format($h['montant'], 2),
                                number_format($h['avant'], 2),
                                number_format($h['apres'], 2),
                                $h['operation']
                            ];
                        }, $historique)
                    );
                }
            } else {
                $this->info("  ✅ OK");
            }
        }
        
        // Afficher le rapport
        if (!empty($incoherences)) {
            $this->table(
                ['Compte', 'Nom', 'Solde Base', 'Solde Calculé', 'Différence', 'Mouvements'],
                array_map(function($inc) {
                    return [
                        $inc['compte'],
                        $inc['nom'],
                        number_format($inc['solde_base'], 2),
                        number_format($inc['solde_calcule'], 2),
                        number_format($inc['difference'], 2),
                        $inc['mouvements']
                    ];
                }, $incoherences)
            );
            
            $this->warn("\n📊 " . count($incoherences) . " incohérence(s) détectée(s)");
            $this->info("💰 Correction totale nécessaire: " . number_format($totalCorrection, 2) . " USD");
            
            // Appliquer la correction si demandé
            if ($force && !$test) {
                if ($this->confirm('Voulez-vous corriger ces soldes ?')) {
                    DB::beginTransaction();
                    try {
                        foreach ($incoherences as $inc) {
                            $compte = Compte::where('numero_compte', $inc['compte'])->first();
                            if ($compte) {
                                $ancienSolde = $compte->solde;
                                $compte->solde = $inc['solde_calcule'];
                                $compte->save();
                                
                                // Créer un mouvement de correction
                                Mouvement::create([
                                    'compte_id' => $compte->id,
                                    'type_mouvement' => 'correction_solde',
                                    'montant' => abs($inc['difference']),
                                    'solde_avant' => $ancienSolde,
                                    'solde_apres' => $inc['solde_calcule'],
                                    'description' => "Correction automatique solde - Ancien: " . 
                                                    number_format($ancienSolde, 2) . 
                                                    " USD, Nouveau: " . 
                                                    number_format($inc['solde_calcule'], 2) . " USD",
                                    'reference' => 'CORRECTION-SOLDE-' . now()->format('YmdHis'),
                                    'date_mouvement' => now(),
                                    'nom_deposant' => 'Système Automatique',
                                    'operateur_id' => 1
                                ]);
                                
                                $this->info("✅ Compte {$inc['compte']} corrigé: " . 
                                          number_format($ancienSolde, 2) . " → " . 
                                          number_format($inc['solde_calcule'], 2));
                            }
                        }
                        
                        DB::commit();
                        $this->info("\n🎯 Correction terminée avec succès !");
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("❌ Erreur lors de la correction: " . $e->getMessage());
                    }
                }
            } elseif ($test) {
                $this->info("\n🧪 MODE TEST - Aucune modification appliquée");
            } else {
                $this->info("\nPour corriger: php artisan soldes:corriger --force");
                $this->info("Pour afficher les détails: php artisan soldes:corriger --details");
                $this->info("Pour tester: php artisan soldes:corriger --test");
            }
        } else {
            $this->info("\n🎯 Tous les soldes sont cohérents !");
        }
        
        return 0;
    }
}