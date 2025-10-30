<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CompteBancaire;
use App\Models\Coffre;
use App\Models\Transaction;
use Exception;

class TransactionService
{
    /**
     * Effectuer une transaction
     *
     * @param string $type            Type de transaction (approvisionnement, decaissement, credit, remboursement)
     * @param CompteBancaire|Coffre|null $source
     * @param CompteBancaire|Coffre|null $destination
     * @param float $montant
     * @param string $devise
     * @param Client|null $client
     * @param string|null $description
     * @return Transaction
     */
    public function effectuer(
        string $type,
        $source = null,
        $destination = null,
        float $montant,
        string $devise,
        ?Client $client = null,
        ?string $description = null
    ): Transaction {
        // Déterminer la source et la destination si elles ne sont pas fournies
        switch ($type) {
            case 'approvisionnement':
                // On alimente le coffre depuis un compte bancaire
                if (!$destination) {
                    $destination = Coffre::where('devise', $devise)->first();
                }
                if (!$source) {
                    throw new Exception("Il faut une source pour l'approvisionnement ({$devise})");
                }
                break;

            case 'decaissement':
                // On retire de la source pour alimenter la destination (coffre)
                if (!$source) {
                    throw new Exception("Il faut une source pour le décaissement ({$devise})");
                }
                if (!$destination) {
                    $destination = Coffre::where('devise', $devise)->first();
                }
                break;

            case 'credit':
            case 'remboursement':
                if (!$client) {
                    throw new Exception("Le client doit être spécifié pour ce type de transaction.");
                }
                if ($type === 'credit') {
                    // On décaisse du coffre ou compte pour donner le crédit
                    if (!$source) {
                        $source = Coffre::where('devise', $devise)->first();
                    }
                    $destination = $client;
                } else {
                    // Remboursement : le client paie, on crédite le coffre
                    $source = $client;
                    if (!$destination) {
                        $destination = Coffre::where('devise', $devise)->first();
                    }
                }
                break;

            default:
                throw new Exception("Type de transaction inconnu : {$type}");
        }

        // Vérifier que la source et la destination ont bien un solde modifiable
        if ($this->hasSolde($source)) {
            if ($source->solde < $montant) {
                throw new Exception("Solde insuffisant pour la source ({$type}, {$devise})");
            }
            $source->solde -= $montant;
            $source->save();
        }

        if ($this->hasSolde($destination)) {
            $destination->solde += $montant;
            $destination->save();
        }

        // Créer la transaction
        return Transaction::create([
            'type' => $type,
            'source_type' => $source ? get_class($source) : null,
            'source_id' => $source ? $source->id : null,
            'destination_type' => $destination ? get_class($destination) : null,
            'destination_id' => $destination ? $destination->id : null,
            'client_id' => $client ? $client->id : null,
            'montant' => $montant,
            'devise' => $devise,
            'description' => $description,
        ]);
    }

    /**
     * Vérifie si le modèle a un champ solde
     */
    private function hasSolde($model): bool
    {
        return $model && in_array('solde', $model->getFillable());
    }
}
