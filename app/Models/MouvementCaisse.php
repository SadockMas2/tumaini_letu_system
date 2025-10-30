<?php
// app/Models/MouvementCaisse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementCaisse extends Model
{
    protected $fillable = [
        'caisse_id',
        'type_mouvement',
        'source_destination',
        'motif',
        'reference',
        'montant',
        'categorie',
        'compte_ohada',
        'user_id',
        'date_mouvement',
        'justificatif'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_mouvement' => 'datetime'
    ];

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function operateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}