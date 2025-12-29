<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Compte;
use App\Models\Mouvement;
use App\Helpers\MouvementHelper;

class CorrigerSoldesSansMouvement extends Command
{
    protected $signature = 'soldes:corriger-silencieux 
        {compte? : Numéro du compte spécifique} 
        {--force : Appliquer la correction}
        {--details : Afficher les détails}
        {--strict : Vérification stricte avec tous les contrôles}';
    
    protected $description = 'Corrige les soldes en ajustant les mouvements existants avec vérification complète';

    public function handle()
    {
        $compteNum = $this->argument('compte');
        $force = $this->option('force');
        $details = $this->option('details');
        $strict = $this->option('strict');
        
        if ($compteNum) {
            $comptes = Compte::where('numero_compte', $compteNum)->get();
            if ($comptes->isEmpty()) {
                $this->error("❌ Compte {$compteNum} non trouvé.");
                return 1;
            }
        } else {
            $comptes = Compte::all();
        }
        
        $this->info("🔍 Analyse de " . $comptes->count() . " comptes...");
        
        $correctionsAppliquer = [];
        $comptesOK = [];
        $comptesErreur = [];
        $comptesSansMouvement = [];
        
        foreach ($comptes as $compte) {
            $this->line("▶️ Compte {$compte->numero_compte} ({$compte->nom} {$compte->postnom} {$compte->prenom})...");
            
            // Récupérer tous les mouvements
            $mouvements = Mouvement::where('compte_id', $compte->id)
                ->orWhere('numero_compte', $compte->numero_compte)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            
            if ($mouvements->isEmpty()) {
                $this->info("  ⚠️  Aucun mouvement trouvé");
                $comptesSansMouvement[] = $compte->numero_compte;
                continue;
            }
            
            // VÉRIFICATIONS MULTIPLES
            $resultats = $this->verifierCohérenceComplete($compte, $mouvements, $strict);
            
            if ($resultats['incoherent']) {
                $this->warn("  ❌ INCOHÉRENCES DÉTECTÉES:");
                
                // Afficher les détails des incohérences
                foreach ($resultats['incoherences'] as $type => $detailsText) {
                    $this->warn("    • {$type}: {$detailsText}");
                }
                
                // Afficher le résumé des différences
                $this->line("    📊 SOLDE COMPTE: " . number_format($resultats['solde_compte'], 2));
                $this->line("    📊 SOLDE CALCULÉ: " . number_format($resultats['solde_calcule'], 2));
                $this->line("    📊 SOLDE DERNIER MOUVEMENT: " . number_format($resultats['solde_dernier_mouvement'], 2));
                
                if ($details && !empty($resultats['historique'])) {
                    $this->table(
                        ['ID', 'Type', 'Montant', 'Avant Réel', 'Après Réel', 'Après Théorique', 'Diff', 'Opération'],
                        array_map(function($h) {
                            $diff = $h['solde_apres_theorique'] - $h['solde_apres_reel'];
                            return [
                                $h['id'],
                                $h['type'],
                                number_format($h['montant'], 2),
                                number_format($h['solde_avant_reel'], 2),
                                number_format($h['solde_apres_reel'], 2),
                                number_format($h['solde_apres_theorique'], 2),
                                number_format($diff, 2),
                                $h['operation']
                            ];
                        }, $resultats['historique'])
                    );
                }
                
                // Proposer la correction
                if ($force) {
                    if ($this->confirm("Corriger le compte {$compte->numero_compte}?")) {
                        $this->corrigerMouvementsComplet($compte, $mouvements, $resultats);
                        $correctionsAppliquer[] = $compte->numero_compte;
                    }
                } else {
                    $correctionsAppliquer[] = [
                        'compte' => $compte->numero_compte,
                        'problemes' => array_keys($resultats['incoherences']),
                        'solde_compte' => $resultats['solde_compte'],
                        'solde_calcule' => $resultats['solde_calcule'],
                        'difference' => $resultats['solde_calcule'] - $resultats['solde_compte']
                    ];
                }
                
                $comptesErreur[] = [
                    'compte' => $compte->numero_compte,
                    'incoherences' => $resultats['incoherences'],
                    'solde_compte' => $resultats['solde_compte'],
                    'solde_calcule' => $resultats['solde_calcule']
                ];
            } else {
                $this->info("  ✅ OK - Toutes les vérifications passées");
                $comptesOK[] = $compte->numero_compte;
            }
        }
        
        // AFFICHER RAPPORT DÉTAILLÉ
        $this->afficherRapport($comptesOK, $comptesErreur, $comptesSansMouvement, $correctionsAppliquer, $force);
        
        return 0;
    }
    
    /**
     * Vérifie la cohérence complète d'un compte
     */
    private function verifierCohérenceComplete(Compte $compte, $mouvements, $strict = false)
    {
        $resultats = [
            'incoherent' => false,
            'incoherences' => [],
            'solde_compte' => round(floatval($compte->solde), 2),
            'solde_calcule' => 0,
            'solde_dernier_mouvement' => 0,
            'premier_mouvement_incoherent' => null,
            'historique' => []
        ];
        
        $soldeTheorique = 0;
        $incoherenceDetectee = false;
        $premierIncoherent = null;
        
        $dernierMouvement = $mouvements->last();
        $resultats['solde_dernier_mouvement'] = $dernierMouvement 
            ? round(floatval($dernierMouvement->solde_apres), 2) 
            : 0;
        
        // ANALYSE DÉTAILLÉE DE CHAQUE MOUVEMENT
        foreach ($mouvements as $index => $mouvement) {
            $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
            $montant = floatval($mouvement->montant);
            $soldeAvantReel = floatval($mouvement->solde_avant);
            $soldeApresReel = floatval($mouvement->solde_apres);
            
            // Vérifier la cohérence interne du mouvement
            if ($index > 0) {
                $mouvementPrecedent = $mouvements[$index - 1];
                $soldeApresPrecedent = floatval($mouvementPrecedent->solde_apres);
                
                if (abs($soldeAvantReel - $soldeApresPrecedent) > 0.01) {
                    $resultats['incoherences']['Chaîne des soldes'] = 
                        "Mvt {$mouvement->id}: solde_avant ({$soldeAvantReel}) != solde_apres précédent ({$soldeApresPrecedent})";
                    $incoherenceDetectee = true;
                }
            }
            
            // Calculer le solde théorique après ce mouvement
            $soldeAvantTheorique = $soldeTheorique;
            
            if ($typeAffichage === 'depot') {
                $soldeTheorique += $montant;
                $operation = 'DEPOT (+)';
            } elseif ($typeAffichage === 'retrait') {
                $soldeTheorique -= $montant;
                $operation = 'RETRAIT (-)';
            } elseif ($typeAffichage === 'neutre') {
                // Les neutres avec montant 0 n'affectent pas le solde
                if (abs($montant) > 0.01) {
                    $soldeTheorique += $montant;
                    $operation = 'NEUTRE';
                } else {
                    $operation = 'NEUTRE (ignoré)';
                }
            } else {
                $soldeTheorique += $montant;
                $operation = 'AUTRE';
            }
            
            $soldeApresTheorique = $soldeTheorique;
            
            // Vérifier l'incohérence entre solde réel et théorique
            $difference = round($soldeApresTheorique - $soldeApresReel, 2);
            
            if (abs($difference) > 0.01 && !$incoherenceDetectee) {
                $incoherenceDetectee = true;
                $premierIncoherent = $index;
                $resultats['premier_mouvement_incoherent'] = $index;
            }
            
            // Enregistrer l'historique pour affichage
            $resultats['historique'][] = [
                'id' => $mouvement->id,
                'type' => $mouvement->type_mouvement,
                'montant' => $montant,
                'solde_avant_reel' => $soldeAvantReel,
                'solde_apres_reel' => $soldeApresReel,
                'solde_apres_theorique' => $soldeApresTheorique,
                'difference' => $difference,
                'operation' => $operation
            ];
        }
        
        $resultats['solde_calcule'] = round($soldeTheorique, 2);
        
        // VÉRIFICATION 1: Solde calculé vs solde du compte
        $diffCompte = round($resultats['solde_calcule'] - $resultats['solde_compte'], 2);
        if (abs($diffCompte) > 0.01) {
            $resultats['incoherences']['Compte vs Calcul'] = 
                "Différence: " . number_format($diffCompte, 2) . 
                " (Compte: " . number_format($resultats['solde_compte'], 2) . 
                ", Calculé: " . number_format($resultats['solde_calcule'], 2) . ")";
            $resultats['incoherent'] = true;
        }
        
        // VÉRIFICATION 2: Solde dernier mouvement vs solde du compte
        $diffDernierMvt = round($resultats['solde_dernier_mouvement'] - $resultats['solde_compte'], 2);
        if (abs($diffDernierMvt) > 0.01) {
            $resultats['incoherences']['Compte vs Dernier mvt'] = 
                "Différence: " . number_format($diffDernierMvt, 2) . 
                " (Compte: " . number_format($resultats['solde_compte'], 2) . 
                ", Dernier mvt: " . number_format($resultats['solde_dernier_mouvement'], 2) . ")";
            $resultats['incoherent'] = true;
        }
        
        // VÉRIFICATION 3: Solde calculé vs solde dernier mouvement
        $diffCalculDernier = round($resultats['solde_calcule'] - $resultats['solde_dernier_mouvement'], 2);
        if (abs($diffCalculDernier) > 0.01) {
            $resultats['incoherences']['Calcul vs Dernier mvt'] = 
                "Différence: " . number_format($diffCalculDernier, 2) . 
                " (Calculé: " . number_format($resultats['solde_calcule'], 2) . 
                ", Dernier mvt: " . number_format($resultats['solde_dernier_mouvement'], 2) . ")";
            $resultats['incoherent'] = true;
        }
        
        // VÉRIFICATION STRICTE (optionnelle)
        if ($strict && !$mouvements->isEmpty()) {
            // Vérifier que le premier mouvement a un solde_avant cohérent
            $premierMouvement = $mouvements->first();
            if (abs(floatval($premierMouvement->solde_avant)) > 0.01 && $index > 0) {
                $resultats['incoherences']['Premier mouvement'] = 
                    "Premier mouvement ({$premierMouvement->id}) devrait avoir solde_avant = 0";
                $resultats['incoherent'] = true;
            }
            
            // Vérifier les montants négatifs pour les dépôts
            foreach ($mouvements as $mouvement) {
                $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
                $montant = floatval($mouvement->montant);
                
                if ($typeAffichage === 'depot' && $montant < 0) {
                    $resultats['incoherences']['Dépôt négatif'] = 
                        "Mouvement {$mouvement->id} marqué comme dépôt mais montant négatif: " . number_format($montant, 2);
                    $resultats['incoherent'] = true;
                }
                
                if ($typeAffichage === 'retrait' && $montant < 0) {
                    $resultats['incoherences']['Retrait négatif'] = 
                        "Mouvement {$mouvement->id} marqué comme retrait mais montant négatif: " . number_format($montant, 2);
                    $resultats['incoherent'] = true;
                }
            }
        }
        
        return $resultats;
    }
    
    /**
     * Corrige les mouvements de manière complète
     */
    private function corrigerMouvementsComplet(Compte $compte, $mouvements, $resultats)
    {
        DB::beginTransaction();
        
        try {
            $this->line("  🔧 Correction en cours...");
            
            $premierIncoherent = $resultats['premier_mouvement_incoherent'];
            $historique = $resultats['historique'];
            
            if ($premierIncoherent === null) {
                // Pas d'incohérence dans la chaîne, juste besoin de mettre à jour le solde final
                $this->corrigerSoldeSeulement($compte, $mouvements, $resultats);
            } else {
                // Incohérence dans la chaîne, besoin de recalculer toute la chaîne
                $this->corrigerChaineComplete($compte, $mouvements, $historique, $premierIncoherent);
            }
            
            DB::commit();
            $this->info("  ✅ Correction terminée pour le compte {$compte->numero_compte}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  ❌ Erreur lors de la correction: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Corrige seulement le solde final (pas d'incohérence dans la chaîne)
     */
    private function corrigerSoldeSeulement(Compte $compte, $mouvements, $resultats)
    {
        $dernierMouvement = $mouvements->last();
        
        // Mettre à jour le solde du compte pour correspondre au solde calculé
        $compte->solde = $resultats['solde_calcule'];
        $compte->save();
        
        // Mettre à jour le solde_apres du dernier mouvement
        if ($dernierMouvement) {
            $dernierMouvement->solde_apres = $resultats['solde_calcule'];
            $dernierMouvement->save();
            
            $this->info("  📊 Solde mis à jour: " . 
                number_format($resultats['solde_compte'], 2) . 
                " → " . 
                number_format($resultats['solde_calcule'], 2));
        }
    }
    
    /**
     * Corrige toute la chaîne de mouvements
     */
    private function corrigerChaineComplete($compte, $mouvements, $historique, $premierIncoherent)
    {
        // Recalculer tous les soldes à partir du premier incohérent
        $soldeCourant = $premierIncoherent > 0 
            ? floatval($mouvements[$premierIncoherent - 1]->solde_apres)
            : 0;
        
        for ($i = $premierIncoherent; $i < count($mouvements); $i++) {
            $mouvement = $mouvements[$i];
            $historiqueMvt = $historique[$i];
            
            // Mettre à jour le solde avant
            $mouvement->solde_avant = $soldeCourant;
            
            // Calculer le nouveau solde après
            $typeAffichage = MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
            $montant = floatval($mouvement->montant);
            
            if ($typeAffichage === 'depot') {
                $soldeCourant += $montant;
            } elseif ($typeAffichage === 'retrait') {
                $soldeCourant -= $montant;
            } elseif ($typeAffichage === 'neutre' && abs($montant) > 0.01) {
                $soldeCourant += $montant;
            } else {
                $soldeCourant += $montant;
            }
            
            // Mettre à jour le solde après
            $mouvement->solde_apres = $soldeCourant;
            $mouvement->save();
            
            if ($this->option('details')) {
                $this->line("    ✓ Mouvement {$mouvement->id}: " . 
                    number_format($historiqueMvt['solde_apres_reel'], 2) . 
                    " → " . 
                    number_format($soldeCourant, 2));
            }
        }
        
        // Mettre à jour le solde final du compte
        $compte->solde = $soldeCourant;
        $compte->save();
        
        $this->info("  📊 Solde final mis à jour: " . number_format($soldeCourant, 2));
    }
    
    /**
     * Affiche un rapport détaillé
     */
    private function afficherRapport($comptesOK, $comptesErreur, $comptesSansMouvement, $correctionsAppliquer, $force)
    {
        $this->info("\n" . str_repeat('=', 60));
        $this->info("📊 RAPPORT DE VÉRIFICATION DES SOLDES");
        $this->info(str_repeat('=', 60));
        
        $this->info("\n✅ COMPTES COHÉRENTS (" . count($comptesOK) . "):");
        if (!empty($comptesOK)) {
            foreach (array_chunk($comptesOK, 10) as $chunk) {
                $this->line("  " . implode(', ', $chunk));
            }
        } else {
            $this->line("  Aucun");
        }
        
        $this->info("\n❌ COMPTES AVEC INCOHÉRENCES (" . count($comptesErreur) . "):");
        foreach ($comptesErreur as $erreur) {
            if (isset($erreur['incoherences']) && is_array($erreur['incoherences'])) {
                $this->warn("  {$erreur['compte']}: " . implode(', ', array_keys($erreur['incoherences'])));
            } elseif (isset($erreur['raison'])) {
                $this->warn("  {$erreur['compte']}: {$erreur['raison']}");
            } else {
                $this->warn("  {$erreur['compte']}: Incohérence détectée");
            }
        }
        
        $this->info("\n⚠️  COMPTES SANS MOUVEMENTS (" . count($comptesSansMouvement) . "):");
        if (!empty($comptesSansMouvement)) {
            foreach (array_chunk($comptesSansMouvement, 10) as $chunk) {
                $this->line("  " . implode(', ', $chunk));
            }
        } else {
            $this->line("  Aucun");
        }
        
        if (!empty($correctionsAppliquer)) {
            if ($force) {
                $this->info("\n🎯 " . count($correctionsAppliquer) . " COMPTE(S) CORRIGÉ(S)");
                foreach ($correctionsAppliquer as $compte) {
                    if (is_array($compte)) {
                        $this->info("  ✓ {$compte['compte']}");
                    } else {
                        $this->info("  ✓ {$compte}");
                    }
                }
            } else {
                $this->warn("\n⚠️  " . count($correctionsAppliquer) . " COMPTE(S) À CORRIGER");
                
                if (isset($correctionsAppliquer[0]) && is_array($correctionsAppliquer[0])) {
                    $this->table(
                        ['Compte', 'Problèmes', 'Solde Compte', 'Solde Calculé', 'Différence'],
                        array_map(function($c) {
                            return [
                                $c['compte'],
                                implode(', ', $c['problemes']),
                                number_format($c['solde_compte'], 2),
                                number_format($c['solde_calcule'], 2),
                                number_format($c['difference'], 2)
                            ];
                        }, $correctionsAppliquer)
                    );
                } else {
                    foreach ($correctionsAppliquer as $compte) {
                        $this->warn("  • {$compte}");
                    }
                }
                
                $this->info("\nPour corriger tous les comptes: php artisan soldes:corriger-silencieux --force");
                $this->info("Pour un compte spécifique: php artisan soldes:corriger-silencieux C00001 --force --details");
                $this->info("Pour une vérification stricte: php artisan soldes:corriger-silencieux --strict");
            }
        } else {
            $this->info("\n🎉 Aucune correction nécessaire !");
        }
        
        // RÉSUMÉ STATISTIQUE
        $totalComptes = count($comptesOK) + count($comptesErreur) + count($comptesSansMouvement);
        $this->info("\n📈 STATISTIQUES:");
        $this->info("  • Total comptes analysés: {$totalComptes}");
        $this->info("  • Comptes cohérents: " . count($comptesOK) . " (" . round(count($comptesOK)/$totalComptes*100, 1) . "%)");
        $this->info("  • Comptes avec incohérences: " . count($comptesErreur) . " (" . round(count($comptesErreur)/$totalComptes*100, 1) . "%)");
        $this->info("  • Comptes sans mouvements: " . count($comptesSansMouvement) . " (" . round(count($comptesSansMouvement)/$totalComptes*100, 1) . "%)");
        
        $this->info("\n" . str_repeat('=', 60));
    }
}