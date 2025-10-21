<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementSalaire extends Model
{
    // Dans la migration de paiement_salaires, ajouter :


// Dans le modèle PaiementSalaire :
protected $fillable = [
  
    'caisse_id', 
    'compte_id', 
    'type_charge',
    'montant',
    'devise',
    'periode',
    'beneficiaire',
    'description',
    'operateur_id',
    'date_paiement',
    'reference'
];

// Relations
public function compte()
{
    return $this->belongsTo(Compte::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}

public function caisse()
{
    return $this->belongsTo(Caisse::class);
}
}
