<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaiementCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'compte_id',
        'montant_paye',
        'date_paiement',
        'type_paiement',
        'reference',
        'statut'
    ];

    protected $casts = [
        'date_paiement' => 'datetime',
        'montant_paye' => 'decimal:2',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function mouvement()
    {
        return $this->hasOne(Mouvement::class, 'reference', 'reference');
    }
}