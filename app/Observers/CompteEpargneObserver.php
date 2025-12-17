<?php

namespace App\Observers;

use App\Models\CompteEpargne;
use App\Models\MouvementEpargne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompteEpargneObserver
{
    /**
     * Quand un compte épargne est crédité (via mouvement)
     */
 public function updated(CompteEpargne $compteEpargne)
    {
        // SUPPRIMEZ la création automatique de mouvement ici
        // Cette logique doit être gérée ailleurs (dans vos controllers)
        
        // Juste logger le changement de solde
        if ($compteEpargne->isDirty('solde')) {
            $ancienSolde = $compteEpargne->getOriginal('solde');
            $nouveauSolde = $compteEpargne->solde;
            
            Log::info('Solde compte épargne modifié', [
                'compte_epargne_id' => $compteEpargne->id,
                'ancien_solde' => $ancienSolde,
                'nouveau_solde' => $nouveauSolde,
                'difference' => $nouveauSolde - $ancienSolde
            ]);
        }
    }

    
    /**
     * Créer un mouvement de retrait d'épargne
     */
    // private function createRetraitMouvement(CompteEpargne $compteEpargne, $montant, $soldeAvant, $soldeApres)
    // {
    //     try {
    //         $mouvement = MouvementEpargne::create([
    //             'compte_epargne_id' => $compteEpargne->id,
    //             'type' => 'retrait',
    //             'montant' => $montant,
    //             'solde_avant' => $soldeAvant,
    //             'solde_apres' => $soldeApres,
    //             'devise' => $compteEpargne->devise,
    //             'description' => 'Retrait d\'épargne',
    //             'reference' => 'RET-' . time() . '-' . $compteEpargne->id,
    //             'operateur_nom' => Auth::user()->name ?? 'Système',
    //             'operateur_id' => Auth::id(),
    //         ]);
            
    //         Log::info('Mouvement retrait épargne créé', [
    //             'compte_epargne_id' => $compteEpargne->id,
    //             'mouvement_id' => $mouvement->id,
    //             'montant' => $montant
    //         ]);
            
    //     } catch (\Exception $e) {
    //         Log::error('Erreur création mouvement retrait épargne', [
    //             'error' => $e->getMessage(),
    //             'compte_epargne_id' => $compteEpargne->id
    //         ]);
    //     }
    // }
}