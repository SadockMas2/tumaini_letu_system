<?php
// app/Models/EntreeRapport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntreeRapport extends Model
{
    protected $fillable = [
        'rapport_coffre_id',
        'provenance',
        'motif',
        'reference', 
        'montant'
    ];

    protected $casts = [
        'montant' => 'decimal:2'
    ];

    public function rapportCoffre(): BelongsTo
    {
        return $this->belongsTo(RapportCoffre::class);
    }
}