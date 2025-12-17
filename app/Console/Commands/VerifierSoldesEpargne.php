<?php

namespace App\Console\Commands;

use App\Models\CompteEpargne;
use App\Models\Epargne;
use App\Models\Mouvement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifierSoldesEpargne extends Command
{
    protected $signature = 'epargne:verifier-soldes';
    protected $description = 'Vérifier et corriger les soldes des comptes épargne';

    public function handle()
    {
        $this->info('🔍 Vérification des soldes des comptes épargne...');
        
        $comptes = CompteEpargne::all();
        $totalCorriges = 0;
        
        foreach ($comptes as $compte) {
            try {
                // Calcul manuel sans utiliser la méthode synchroniserSolde
                $soldeCorrect = $this->calculerSoldeCorrect($compte);
                
                $ecart = $compte->solde - $soldeCorrect;
                
                if (abs($ecart) > 0.01) {
                    $this->warn("✓ Correction nécessaire pour {$compte->numero_compte}");
                    $this->line("  Solde actuel: {$compte->solde}");
                    $this->line("  Solde calculé: {$soldeCorrect}");
                    $this->line("  Écart: {$ecart}");
                    
                    if ($this->confirm("Corriger le solde de {$compte->numero_compte}?")) {
                        $compte->solde = $soldeCorrect;
                        $compte->save();
                        $totalCorriges++;
                        $this->info("  ✅ Solde corrigé");
                    }
                } else {
                    $this->line("✓ {$compte->numero_compte}: OK");
                }
                
            } catch (\Exception $e) {
                $this->error("✗ Erreur avec {$compte->numero_compte}: " . $e->getMessage());
            }
        }
        
        $this->info("\n✅ Vérification terminée. {$totalCorriges} comptes corrigés sur {$comptes->count()}");
        
        Log::info("Vérification soldes épargne terminée", [
            'total_comptes' => $comptes->count(),
            'corriges' => $totalCorriges
        ]);
        
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
    
    // Modifiez la méthode calculerSoldeCorrect dans VerifierSoldesEpargne.php

private function calculerSoldeCorrect($compte)
{
    // SEULEMENT les épargnes VALIDES
    $totalEpargnes = 0;
    
    if ($compte->type_compte === 'individuel' && $compte->client_id) {
        $totalEpargnes = Epargne::where('client_id', $compte->client_id)
            ->where('statut', 'valide')
            ->where('devise', $compte->devise)
            ->sum('montant');
    } elseif ($compte->type_compte === 'groupe_solidaire' && $compte->groupe_solidaire_id) {
        $totalEpargnes = Epargne::where('groupe_solidaire_id', $compte->groupe_solidaire_id)
            ->where('statut', 'valide')
            ->where('devise', $compte->devise)
            ->sum('montant');
    }
    
    // IGNORER les mouvements d'épargne - ce sont des doublons
    $totalRetraits = Mouvement::where('compte_epargne_id', $compte->id)
        ->where('type', 'retrait')
        ->sum('montant');
    
    return $totalEpargnes - $totalRetraits;
}
}