<?php

namespace App\Console\Commands;

use App\Models\CompteEpargne;
use App\Models\Epargne;
use App\Models\Mouvement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValiderSoldesEpargne extends Command
{
    protected $signature = 'epargne:valider-donnees';
    protected $description = 'Valider la cohérence des données entre épargnes et mouvements';

    public function handle()
    {
        $this->info('🔍 Validation des données d\'épargne...');
        
        $comptes = CompteEpargne::all();
        $incoherences = [];
        
        foreach ($comptes as $compte) {
            // Compter les épargnes par statut
            $stats = [];
            
            if ($compte->type_compte === 'individuel' && $compte->client_id) {
                $stats = Epargne::where('client_id', $compte->client_id)
                    ->where('devise', $compte->devise)
                    ->select('statut', DB::raw('COUNT(*) as count'), DB::raw('SUM(montant) as total'))
                    ->groupBy('statut')
                    ->get()
                    ->keyBy('statut');
            }
            
            // Vérifier si certaines épargnes ne sont pas validées
            $nonValidees = $stats['en_attente_dispatch'] ?? null;
            
            if ($nonValidees) {
                $incoherences[] = [
                    'compte' => $compte->numero_compte,
                    'client' => $compte->nom_complet,
                    'statut' => 'Épargnes en attente',
                    'nombre' => $nonValidees->count,
                    'montant' => $nonValidees->total,
                    'solde_actuel' => $compte->solde,
                    'message' => 'Il y a des épargnes non encore dispatcher'
                ];
            }
        }
        
        if (count($incoherences) > 0) {
            $this->table(
                ['Compte', 'Client', 'Statut', 'Nb', 'Montant', 'Solde', 'Message'],
                $incoherences
            );
            
            $this->warn("\n⚠️ " . count($incoherences) . " comptes ont des épargnes non validées");
        } else {
            $this->info("✅ Toutes les données sont cohérentes");
        }
        
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}