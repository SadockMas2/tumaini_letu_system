<?php
// app/Models/MouvementCoffre.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementCoffre extends Model
{
    protected $fillable = [
        'coffre_fort_id',
        'type_mouvement',
        'provenance_destination',
        'motif',
        'reference',
        'montant',
        'user_id',
        'date_mouvement',
        'observations'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_mouvement' => 'datetime'
    ];

    public function coffre(): BelongsTo
    {
        return $this->belongsTo(CoffreFort::class);
    }

    public function operateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}