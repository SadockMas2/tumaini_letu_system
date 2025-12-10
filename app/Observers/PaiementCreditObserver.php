<?php

namespace App\Observers;

use App\Models\PaiementCredit;
use App\Models\Credit;
use App\Models\CreditGroupe;
use Illuminate\Support\Facades\Log;

class PaiementCreditObserver
{
    /**
     * Handle the PaiementCredit "created" event.
     */
    public function created(PaiementCredit $paiement): void
    {
        // NE PLUS rédure le capital, seulement les intérêts
        $this->reduireSeulementInterets($paiement);
    }

    /**
     * Réduit SEULEMENT les intérêts, PAS le capital
     */
    private function reduireSeulementInterets(PaiementCredit $paiement): void
    {
        // Pour les crédits individuels
        if ($paiement->credit_id && $paiement->interets_payes > 0) {
            $credit = Credit::find($paiement->credit_id);
            if ($credit) {
                // Récupérer le montant accordé original
                $montantAccordeOriginal = $credit->montant_accorde;
                
                // Réduire SEULEMENT les intérêts
                $nouveauMontantTotal = max(
                    $montantAccordeOriginal, // Minimum = montant accordé
                    $credit->montant_total - $paiement->interets_payes
                );
                
                $credit->montant_total = $nouveauMontantTotal;
                $credit->save();
                
                Log::info('Observer: Intérêts réduits (capital PROTÉGÉ)', [
                    'paiement_id' => $paiement->id,
                    'credit_id' => $credit->id,
                    'interets_payes' => $paiement->interets_payes,
                    'capital_rembourse' => $paiement->capital_rembourse,
                    'montant_accorde_fixe' => $montantAccordeOriginal,
                    'nouveau_montant_total' => $nouveauMontantTotal
                ]);
            }
        }
        
        // Pour les crédits groupe
        if ($paiement->credit_groupe_id && $paiement->interets_payes > 0) {
            $creditGroupe = CreditGroupe::find($paiement->credit_groupe_id);
            if ($creditGroupe) {
                // Récupérer le montant accordé original
                $montantAccordeOriginal = $creditGroupe->montant_accorde;
                
                // Réduire SEULEMENT les intérêts
                $nouveauMontantTotal = max(
                    $montantAccordeOriginal,
                    $creditGroupe->montant_total - $paiement->interets_payes
                );
                
                $creditGroupe->montant_total = $nouveauMontantTotal;
                $creditGroupe->save();
            }
        }
    }
}