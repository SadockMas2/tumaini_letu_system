<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Echeancier extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'credit_groupe_id', 
        'compte_id',
        'semaine',
        'date_echeance',
        'montant_a_payer',
        'capital_restant',
        'statut',
        'date_paiement'
    ];

    protected $casts = [
        'date_echeance' => 'date',
        'date_paiement' => 'datetime',
        'montant_a_payer' => 'decimal:2',
        'capital_restant' => 'decimal:2',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function creditGroupe()
    {
        return $this->belongsTo(CreditGroupe::class);
    }

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}