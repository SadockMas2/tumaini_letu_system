<?php
// app/Models/PlanComptable.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanComptable extends Model
{
    protected $fillable = [
        'numero_compte',
        'libelle',
        'classe',
        'type_compte',
        'sous_type',
        'compte_de_tiers',
        'statut'
    ];

    public function ecritures(): HasMany
    {
        return $this->hasMany(EcritureComptable::class, 'compte_number', 'numero_compte');
    }
}