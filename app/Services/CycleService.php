<?php

namespace App\Services;

use App\Models\Cycle;
use App\Models\Epargne;
use App\Models\CompteSpecial;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;

class CycleService
{
    /**
     * Créer un nouveau cycle d'épargne
     */
    public function creerCycle(array $data): Cycle
    {
        return DB::transaction(function () use ($data) {
            $cycle = Cycle::create($data);
            
            // Créditer le compte spécial UNIQUEMENT ici
            if ($cycle->solde_initial > 0) {
                $cycle->crediterCompteSpecial();
            }
            
            return $cycle;
        });
    }

    /**
     * Ajouter une épargne à un cycle
     */
    public function ajouterEpargne(array $data): Epargne
    {
        return DB::transaction(function () use ($data) {
            $epargne = Epargne::create($data);
            
            return $epargne;
        });
    }

    /**
     * Clôturer un cycle et traiter les soldes
     */
    public function cloturerCycle(int $cycleId): Cycle
    {
        return DB::transaction(function () use ($cycleId) {
            $cycle = Cycle::findOrFail($cycleId);
            
            // Vérifier que toutes les épargnes sont validées
            $epargnesEnAttente = Epargne::where('cycle_id', $cycleId)
                ->whereIn('statut', ['en_attente_dispatch', 'en_attente_validation'])
                ->exists();
            
            if ($epargnesEnAttente) {
                throw new \Exception('Impossible de clôturer le cycle : des épargnes sont en attente');
            }

            $cycle->fermer();
            
            return $cycle;
        });
    }

    /**
     * Récupérer le solde total d'un cycle
     */
    public function getSoldeCycle(int $cycleId): array
    {
        $cycle = Cycle::findOrFail($cycleId);
        
        $soldeInitial = $cycle->solde_initial;
        $totalEpargnes = Epargne::where('cycle_id', $cycleId)
            ->where('statut', 'valide')
            ->sum('montant');
        
        $soldeCompteSpecial = $soldeInitial; // Le solde initial est dans le compte spécial
        $soldeMembres = $totalEpargnes; // Les épargnes suivantes sont dans les comptes membres/groupes
        
        return [
            'solde_initial' => $soldeInitial,
            'total_epargnes' => $totalEpargnes,
            'solde_compte_special' => $soldeCompteSpecial,
            'solde_membres' => $soldeMembres,
            'solde_total' => $soldeInitial + $totalEpargnes,
        ];
    }
}