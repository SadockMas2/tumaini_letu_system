<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\MouvementHelper;

class FixSoldeFinal extends Command
{
    protected $signature = 'fix:solde-final 
                           {compte : Numéro du compte}
                           {--apply : Appliquer la correction}';
    
    protected $description = 'Correction FINALE des soldes';
    
    public function handle()
    {
        $compteNum = $this->argument('compte');
        $apply = $this->option('apply');
        
        $compte = DB::table('comptes')
            ->where('numero_compte', $compteNum)
            ->first();
            
        if (!$compte) {
            $this->error("❌ Compte {$compteNum} non trouvé");
            return 1;
        }
        
        $this->info("🎯 COMPTE {$compteNum}");
        $this->info("💰 Solde actuel : " . number_format($compte->solde, 2));
        
        // RÈGLE SIMPLE POUR CALCULER LE SOLDE :
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compte->id)
            ->orderBy('date_mouvement', 'asc')
            ->get(['id', 'type_mouvement', 'montant']);
        
        $soldeCalcule = 0;
        
        foreach ($mouvements as $m) {
            // DÉTERMINER L'OPÉRATION selon le TYPE
            switch ($m->type_mouvement) {
                case 'frais_payes_credit':
                case 'frais_payes_credit_groupe':
                case 'paiement_credit':
                case 'paiement_credit_groupe':
                    // Ces types ont des montants DÉJÀ NÉGATIFS dans la base
                    // Ex: -44.00, -75.48
                    // On les AJOUTE simplement
                    $soldeCalcule += $m->montant;
                    break;
                    
                case 'retrait_compte':
                case 'frais_adhesion':
                case 'frais_service':
                case 'commission':
                case 'frais_ouverture_compte':
                case 'frais_gestion':
                case 'debit_automatique':
                    // Ces types ont des montants POSITIFS mais sont des RETRAITS
                    // Ex: 497.00, 1.00
                    // On les SOUSTRAIT
                    $soldeCalcule -= $m->montant;
                    break;
                    
                case 'depot_compte':
                case 'credit_octroye':
                case 'credit_groupe_recu':
                case 'remboursement':
                case 'interet':
                case 'bonus':
                    // Ces types ont des montants POSITIFS et sont des DÉPÔTS
                    // Ex: 125.00, 500.00
                    // On les AJOUTE
                    $soldeCalcule += $m->montant;
                    break;
                    
                default:
                    // Pour les autres, utiliser MouvementHelper
                    $typeAffichage = MouvementHelper::getTypeAffichage($m->type_mouvement);
                    
                    if ($typeAffichage === 'depot') {
                        $soldeCalcule += $m->montant;
                    } elseif ($typeAffichage === 'retrait') {
                        $soldeCalcule -= $m->montant;
                    } else {
                        $soldeCalcule += $m->montant;
                    }
                    break;
            }
        }
        
        $soldeCalcule = round($soldeCalcule, 2);
        $difference = $soldeCalcule - $compte->solde;
        
        $this->info("💰 Solde calculé : " . number_format($soldeCalcule, 2));
        $this->info("📈 Différence : " . number_format($difference, 2));
        
        if (abs($difference) < 0.01) {
            $this->info("\n✅ Le solde est déjà correct !");
            return 0;
        }
        
        // Afficher le détail des opérations problématiques
        $this->afficherDetailErreurs($mouvements);
        
        if ($apply) {
            return $this->appliquerCorrectionFinale($compte, $soldeCalcule, $mouvements);
        } else {
            $this->warn("\n⚠️  Pour appliquer cette correction :");
            $this->info("php artisan fix:solde-final {$compteNum} --apply");
        }
        
        return 0;
    }
    
    private function afficherDetailErreurs($mouvements)
    {
        $this->info("\n🔍 DÉTAIL DES OPÉRATIONS PROBLÉMATIQUES :");
        
        $solde = 0;
        $problemes = [];
        
        foreach ($mouvements as $m) {
            $avant = $solde;
            
            // Calcul selon la règle
            if (in_array($m->type_mouvement, ['frais_payes_credit', 'paiement_credit'])) {
                $solde += $m->montant;
                $operation = "AJOUT " . number_format($m->montant, 2) . " (déjà signé)";
            } elseif (in_array($m->type_mouvement, ['retrait_compte', 'frais_adhesion'])) {
                $solde -= $m->montant;
                $operation = "SOUSTRAIT " . number_format($m->montant, 2);
            } else {
                $solde += $m->montant;
                $operation = "AJOUT " . number_format($m->montant, 2);
            }
            
            $apres = $solde;
            
            // Vérifier si cette opération créerait un problème
            if (($m->type_mouvement === 'frais_payes_credit' && $m->montant < 0 && $apres < $avant) ||
                ($m->type_mouvement === 'retrait_compte' && $m->montant > 0 && $apres > $avant)) {
                $problemes[] = [
                    'id' => $m->id,
                    'type' => $m->type_mouvement,
                    'montant' => number_format($m->montant, 2),
                    'operation' => $operation,
                    'resultat' => number_format($apres, 2)
                ];
            }
        }
        
        if (!empty($problemes)) {
            $this->table(
                ['ID', 'Type', 'Montant', 'Opération', 'Résultat'],
                $problemes
            );
        }
    }
    
    private function appliquerCorrectionFinale($compte, $nouveauSolde, $mouvements)
    {
        $this->warn("\n⚠️  CORRECTION FINALE...");
        
        if (!$this->confirm("Confirmez-vous la correction ?")) {
            $this->error('❌ Annulé');
            return 1;
        }
        
        try {
            DB::beginTransaction();
            
            // 1. Mettre à jour le compte
            DB::table('comptes')
                ->where('id', $compte->id)
                ->update(['solde' => $nouveauSolde]);
            
            // 2. Recalculer les soldes des mouvements
            $soldeCourant = 0;
            
            foreach ($mouvements as $m) {
                $soldeAvant = $soldeCourant;
                
                // Même logique
                if (in_array($m->type_mouvement, ['frais_payes_credit', 'paiement_credit'])) {
                    $soldeCourant += $m->montant;
                } elseif (in_array($m->type_mouvement, ['retrait_compte', 'frais_adhesion'])) {
                    $soldeCourant -= $m->montant;
                } else {
                    $soldeCourant += $m->montant;
                }
                
                DB::table('mouvements')
                    ->where('id', $m->id)
                    ->update([
                        'solde_avant' => $soldeAvant,
                        'solde_apres' => $soldeCourant
                    ]);
            }
            
            DB::commit();
            
            $this->info("\n✅ CORRECTION APPLIQUÉE AVEC SUCCÈS !");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Erreur : ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}