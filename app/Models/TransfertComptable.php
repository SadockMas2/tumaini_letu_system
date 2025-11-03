<?php
// app/Models/TransfertComptable.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransfertComptable extends Model
{
    use HasFactory;

    protected $fillable = [
        'comptabilite_source_id',
        'comptabilite_destination_id',
        'type_transfert',
        'montant',
        'devise',
        'reference',
        'description',
        'statut',
        'date_transfert',
        'operateur_id'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_transfert' => 'datetime'
    ];

    public function comptabiliteSource()
    {
        return $this->belongsTo(Comptabilite::class, 'comptabilite_source_id');
    }

    public function comptabiliteDestination()
    {
        return $this->belongsTo(Comptabilite::class, 'comptabilite_destination_id');
    }

    public function operateur()
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }

    public function ecritures()
    {
        return $this->hasMany(EcritureComptable::class, 'reference_operation', 'reference');
    }
}