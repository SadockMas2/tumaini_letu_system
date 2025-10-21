<?php
// app/Services/CoffreService.php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\RapportCoffre;
use App\Models\MouvementCoffre;
use Illuminate\Support\Facades\Auth;

class CoffreService
{
    public function genererRapportQuotidien($coffreId, $date, $soldePhysique, $observations = null)
    {
        return RapportCoffre::genererRapportQuotidien($coffreId, $date, $soldePhysique, $observations);
    }

    public function alimenterCoffre($coffreId, $montant, $source, $reference, $devise = 'USD', $description = null)
    {
        $coffre = CashRegister::findOrFail($coffreId);
        
        // Créer mouvement physique
        $mouvement = MouvementCoffre::create([
            'coffre_destination_id' => $coffre->id,
            'type_mouvement' => 'entree',
            'montant' => $montant,
            'devise' => $devise,
            'source_type' => $source,
            'reference' => $reference,
            'description' => $description ?? "Alimentation depuis {$source}",
            'date_mouvement' => now(),
            'operateur_id' => Auth::id()
        ]);

        // Mettre à jour le solde du coffre
        $coffre->solde_actuel += $montant;
        $coffre->save();

        return $mouvement;
    }

    public function transfererVersComptable($coffreId, $montant, $devise = 'USD', $motif = 'Transfert comptable')
    {
        $coffre = CashRegister::findOrFail($coffreId);
        
        if ($coffre->solde_actuel < $montant) {
            throw new \Exception('Solde insuffisant dans le coffre');
        }

        // Créer mouvement physique
        $mouvement = MouvementCoffre::create([
            'coffre_source_id' => $coffre->id,
            'type_mouvement' => 'sortie',
            'montant' => $montant,
            'devise' => $devise,
            'destination_type' => 'comptable',
            'reference' => 'TRANSF-COMPT-' . now()->format('YmdHis'),
            'description' => "Transfert vers comptable - {$motif}",
            'date_mouvement' => now(),
            'operateur_id' => Auth::id()
        ]);

        // Mettre à jour le solde du coffre
        $coffre->solde_actuel -= $montant;
        $coffre->save();

        return $mouvement;
    }
}