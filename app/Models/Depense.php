<?php
// app/Models/Depense.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depense extends Model
{
    protected $fillable = [
        'caisse_id', // AJOUTÉ : pour lier la dépense à une caisse spécifique
        'categorie',
        'montant',
        'devise', // AJOUTÉ : pour enregistrer la devise de la dépense
        'beneficiaire',
        'description',
        'operateur_id',
        'date_depense',
        'reference'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_depense' => 'datetime'
    ];

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function operateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }
}