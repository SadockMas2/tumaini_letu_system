<?php
// app/Models/SortieRapport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SortieRapport extends Model
{
    protected $fillable = [
        'rapport_coffre_id',
        'destination',
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