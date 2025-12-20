<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestaurerSoldes extends Command
{
    protected $signature = 'restaurer:soldes 
                           {--compte-id= : ID spécifique d\'un compte}
                           {--dry-run : Voir les restaurations sans les appliquer}
                           {--date= : Date de référence (format: YYYY-MM-DD HH:MM:SS)}';
    
    protected $description = 'Restaure les soldes à partir des derniers mouvements corrects';
    
    public function handle()
    {
        $compteId = $this->option('compte-id');
        $dryRun = $this->option('dry-run');
        $dateReference = $this->option('date') ?: now()->subHours(24)->format('Y-m-d H:i:s');
        
        $this->info('🔙 Restauration des soldes...');
        $this->info("📅 Date de référence : {$dateReference}");
        
        try {
            // 1. RÉCUPÉRER LES COMPTES
            $query = DB::table('comptes')->select('id', 'numero_compte', 'solde');
            
            if ($compteId) {
                $query->where('id', $compteId);
            }
            
            $comptes = $query->get();
            
            $this->info("📊 Comptes à vérifier : {$comptes->count()}");
            
            $restaurations = [];
            
            foreach ($comptes as $compte) {
                // 2. TROUVER LE DERNIER MOUVEMENT AVANT LA DATE DE RÉFÉRENCE
                $dernierMouvement = DB::table('mouvements')
                    ->where('compte_id', $compte->id)
                    ->where('created_at', '<', $dateReference)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->first(['solde_apres', 'created_at', 'type_mouvement', 'description']);
                
                if ($dernierMouvement) {
                    // 3. CALCULER LE NOUVEAU SOLDE
                    $soldeRestaurer = $dernierMouvement->solde_apres;
                    
                    // Ajouter les mouvements APRÈS la date de référence
                    $mouvementsApres = DB::table('mouvements')
                        ->where('compte_id', $compte->id)
                        ->where('created_at', '>=', $dateReference)
                        ->sum('montant');
                    
                    $nouveauSolde = $soldeRestaurer + $mouvementsApres;
                    $soldeActuel = $compte->solde;
                    $difference = $nouveauSolde - $soldeActuel;
                    
                    if (abs($difference) > 0.01) {
                        $restaurations[] = [
                            'id' => $compte->id,
                            'compte' => $compte->numero_compte,
                            'solde_actuel' => $soldeActuel,
                            'solde_restaurer' => $soldeRestaurer,
                            'mouvements_apres' => $mouvementsApres,
                            'nouveau_solde' => $nouveauSolde,
                            'difference' => $difference,
                            'dernier_mouvement_date' => $dernierMouvement->created_at
                        ];
                        
                        if (!$dryRun) {
                            // Mettre à jour le solde du compte
                            DB::table('comptes')
                                ->where('id', $compte->id)
                                ->update(['solde' => $nouveauSolde]);
                            
                            // Recalculer les soldes des mouvements
                            $this->recalculerSoldesMouvementsDepuisDate($compte->id, $dateReference, $soldeRestaurer);
                        }
                    }
                } else {
                    // Pas de mouvement avant la date de référence
                    $mouvementsTotal = DB::table('mouvements')
                        ->where('compte_id', $compte->id)
                        ->sum('montant');
                    
                    if (abs($compte->solde - $mouvementsTotal) > 0.01) {
                        $restaurations[] = [
                            'id' => $compte->id,
                            'compte' => $compte->numero_compte,
                            'solde_actuel' => $compte->solde,
                            'solde_restaurer' => 0,
                            'mouvements_apres' => $mouvementsTotal,
                            'nouveau_solde' => $mouvementsTotal,
                            'difference' => $mouvementsTotal - $compte->solde,
                            'dernier_mouvement_date' => 'Aucun mouvement avant référence'
                        ];
                        
                        if (!$dryRun) {
                            DB::table('comptes')
                                ->where('id', $compte->id)
                                ->update(['solde' => $mouvementsTotal]);
                            
                            $this->recalculerSoldesMouvements($compte->id);
                        }
                    }
                }
            }
            
            // 4. AFFICHER LES RÉSULTATS
            if (!empty($restaurations)) {
                $this->info("\n📋 RESTAURATIONS PLANIFIÉES :");
                
                $this->table(
                    ['ID', 'Compte', 'Solde actuel', 'Solde à restaurer', 'Nouveau solde', 'Différence', 'Date référence'],
                    array_map(function($item) {
                        return [
                            $item['id'],
                            $item['compte'],
                            number_format($item['solde_actuel'], 2),
                            number_format($item['solde_restaurer'], 2),
                            number_format($item['nouveau_solde'], 2),
                            number_format($item['difference'], 2),
                            substr($item['dernier_mouvement_date'], 0, 16)
                        ];
                    }, $restaurations)
                );
                
                if ($dryRun) {
                    $this->warn("\n🔍 Mode DRY RUN - Aucune modification appliquée");
                    $this->info("Pour appliquer : php artisan restaurer:soldes");
                } else {
                    $this->info("\n✅ " . count($restaurations) . " comptes restaurés");
                    Log::info('Restauration des soldes', [
                        'comptes_restaures' => count($restaurations),
                        'date_reference' => $dateReference
                    ]);
                }
            } else {
                $this->info("\n✅ Tous les soldes semblent déjà corrects !");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
            Log::error('Erreur restauration soldes', ['error' => $e->getMessage()]);
            return 1;
        }
        
        return 0;
    }
    
    private function recalculerSoldesMouvementsDepuisDate($compteId, $dateReference, $soldeInitial)
    {
        // Récupérer tous les mouvements APRÈS la date de référence
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->where('created_at', '>=', $dateReference)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'montant']);
        
        $soldeCourant = $soldeInitial;
        
        foreach ($mouvements as $mouvement) {
            $soldeAvant = $soldeCourant;
            $soldeCourant += $mouvement->montant;
            
            DB::table('mouvements')
                ->where('id', $mouvement->id)
                ->update([
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $soldeCourant
                ]);
        }
    }
    
    private function recalculerSoldesMouvements($compteId)
    {
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'montant']);
        
        $soldeCourant = 0;
        
        foreach ($mouvements as $mouvement) {
            $soldeAvant = $soldeCourant;
            $soldeCourant += $mouvement->montant;
            
            DB::table('mouvements')
                ->where('id', $mouvement->id)
                ->update([
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $soldeCourant
                ]);
        }
    }
}