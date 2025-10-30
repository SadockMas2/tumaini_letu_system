<?php
// app/Services/SupplyChainService.php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\Caisse;
use App\Models\MouvementCoffre;
use App\Models\RapportCoffre;
use Illuminate\Support\Facades\DB;

class SupplyChainService
{
    public function __construct(private ComptabilityService $comptabilityService)
    {
    }

    public function processusApprovisionnementBanque(int $coffreId, float $montant, string $referenceBanque, string $devise = 'USD')
    {
        return DB::transaction(function () use ($coffreId, $montant, $referenceBanque, $devise) {
            $coffre = CashRegister::findOrFail($coffreId);
            $mouvement = $coffre->alimenter($montant, 'banque', $referenceBanque);
            
            $this->comptabilityService->enregistrerAlimentationCoffre($mouvement->id, $referenceBanque);
            
            return $mouvement;
        });
    }

    public function transfererVersComptable(int $coffreId, float $montant, string $motif)
    {
        return DB::transaction(function () use ($coffreId, $montant, $motif) {
            $coffre = CashRegister::findOrFail($coffreId);
            
            if (!$coffre->peutTransferer($montant)) {
                throw new \Exception('Solde insuffisant dans le coffre pour ce transfert');
            }

            $reference = 'TRANSF-COMPT-' . now()->format('YmdHis');
            $mouvement = $coffre->transfererVersCaisse($montant, 'comptable', $reference);
            $this->comptabilityService->enregistrerTransfertCoffreVersComptable($mouvement->id);

            return $mouvement;
        });
    }

    public function distribuerFondsCaisses(int $comptableId, array $distributions)
    {
        return DB::transaction(function () use ($comptableId, $distributions) {
            $results = [];

            foreach ($distributions as $typeCaisse => $montant) {
                if ($montant > 0) {
                    $caisse = Caisse::where('type_caisse', $typeCaisse)
                        ->where('comptable_id', $comptableId)
                        ->firstOrFail();

                    $reference = 'DISTRIB-' . now()->format('YmdHis');
                    $caisse->recevoirAlimentation($montant, $reference);
                    
                    $results[$typeCaisse] = [
                        'caisse' => $caisse,
                        'montant' => $montant,
                        'reference' => $reference
                    ];
                }
            }

            return $results;
        });
    }

    public function processusFinJournee(int $coffreId, float $soldePhysique, string $observations = null)
    {
        return DB::transaction(function () use ($coffreId, $soldePhysique, $observations) {
            $rapport = RapportCoffre::genererRapportQuotidien($coffreId);
            $this->reintegrerFondsCaissesVersCoffre($coffreId);
            $rapport->finaliser($soldePhysique, $observations);

            $dateAujourdhui = now()->format('Y-m-d');
            $estEquilibre = $this->comptabilityService->verifierEquilibrePeriodique($dateAujourdhui, $dateAujourdhui);

            if (!$estEquilibre) {
                throw new \Exception('Alerte: Les écritures comptables ne sont pas équilibrées pour cette journée');
            }

            return [
                'rapport' => $rapport,
                'equilibre_comptable' => $estEquilibre
            ];
        });
    }

    private function reintegrerFondsCaissesVersCoffre(int $coffreId)
    {
        $coffre = CashRegister::findOrFail($coffreId);
        $caisses = Caisse::where('agence_id', $coffre->agence_id)->get();

        foreach ($caisses as $caisse) {
            if ($caisse->solde_actuel > 0) {
                $reference = 'RETOUR-FIN-JOURNEE-' . now()->format('Ymd');

                MouvementCoffre::create([
                    'coffre_destination_id' => $coffreId,
                    'type_mouvement' => 'entree',
                    'montant' => $caisse->solde_actuel,
                    'devise' => $caisse->devise,
                    'source_type' => $caisse->type_caisse,
                    'reference' => $reference,
                    'description' => "Retour fin de journée de la {$caisse->type_caisse}",
                    'date_mouvement' => now()
                ]);

                $coffre->solde_actuel += $caisse->solde_actuel;
                $coffre->save();

                $caisse->solde_actuel = 0;
                $caisse->save();
            }
        }
    }
}