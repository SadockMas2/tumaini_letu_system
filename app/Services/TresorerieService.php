<?php
// app/Services/TresorerieService.php

namespace App\Services;

use App\Models\Caisse;
use App\Models\Mouvement;
use App\Models\RapportTresorerie;
use App\Models\RapportTresorerieCaisse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TresorerieService
{
    public function genererRapportFinJournee($date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        
        return DB::transaction(function () use ($date) {
            // Vérifier si un rapport existe déjà pour cette date
            $rapportExistant = RapportTresorerie::whereDate('date_rapport', $date)->first();
            if ($rapportExistant) {
                throw new \Exception('Un rapport existe déjà pour cette date.');
            }

            // Créer le rapport
            $rapport = RapportTresorerie::create([
                'date_rapport' => $date,
                'statut' => 'brouillon'
            ]);

            $totalDepots = 0;
            $totalRetraits = 0;
            $nombreOperations = 0;
            $soldeTotalCaisses = 0;

            // Traiter chaque caisse
            $caisses = Caisse::all();
            foreach ($caisses as $caisse) {
                $mouvements = Mouvement::where('caisse_id', $caisse->id)
                    ->whereDate('created_at', $date)
                    ->get();

                $depotsCaisse = $mouvements->where('type', 'depot')->sum('montant');
                $retraitsCaisse = $mouvements->where('type', 'retrait')->sum('montant');
                $operationsCaisse = $mouvements->count();

                // Créer le détail pour cette caisse
                RapportTresorerieCaisse::create([
                    'rapport_tresorerie_id' => $rapport->id,
                    'caisse_id' => $caisse->id,
                    'type_caisse' => $caisse->type_caisse,
                    'solde_initial' => $caisse->solde_actuel - ($depotsCaisse - $retraitsCaisse),
                    'solde_final' => $caisse->solde_actuel,
                    'nombre_operations' => $operationsCaisse,
                    'total_mouvements' => $depotsCaisse + $retraitsCaisse
                ]);

                $totalDepots += $depotsCaisse;
                $totalRetraits += $retraitsCaisse;
                $nombreOperations += $operationsCaisse;
                $soldeTotalCaisses += $caisse->solde_actuel;
            }

            // Mettre à jour les totaux du rapport
            $rapport->update([
                'total_depots' => $totalDepots,
                'total_retraits' => $totalRetraits,
                'nombre_operations' => $nombreOperations,
                'solde_total_caisses' => $soldeTotalCaisses,
                'statut' => 'finalise'
            ]);

            return $rapport;
        });
    }

    public function transfererVersComptabilite(RapportTresorerie $rapport)
    {
        return DB::transaction(function () use ($rapport) {
            // Logique de transfert vers la comptabilité
            // Ici vous pouvez intégrer avec votre système comptable
            
            $rapport->update([
                'statut' => 'transfere_comptabilite',
                'observations' => 'Transféré vers comptabilité le ' . now()->format('d/m/Y H:i')
            ]);

            return $rapport;
        });
    }
}