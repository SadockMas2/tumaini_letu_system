<?php

namespace App\Enums;

enum TypePaiement: string
{
    case ESPECES = 'especes';
    case MOBILE = 'mobile_money';
    case BANCAIRE = 'transfert_bancaire';
    case AUTOMATIQUE = 'auto';
    case CHEQUE = 'cheque';
     case GROUPE = 'groupe'; // AJOUT DE LA NOUVELLE CONSTANTE

    
    public function getLabel(): string
    {
        return match($this) {
            self::ESPECES => 'Espèces',
            self::MOBILE => 'Mobile Money',
            self::BANCAIRE => 'Transfert Bancaire',
            self::AUTOMATIQUE => 'Prélèvement Automatique',
            self::CHEQUE => 'Chèque',
            self::GROUPE => 'Paiement Groupe', // AJOUT DU LABEL

        };
    }
}