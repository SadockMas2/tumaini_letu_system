<?php
// app/Models/MouvementCoffre.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementCoffre extends Model
{
    protected $fillable = [
        'coffre_source_id',
         'coffre_destination_id',
        
          // Coffre concerné
        'type_mouvement',      // 'entree' ou 'sortie'
        'montant',
        'devise',
        'source_type',         // 'banque', 'partenaire', etc.
        'destination_type',    // 'comptabilite', etc.
        'reference',
        'description',
        'date_mouvement',
        'operateur_id'
    ];

    protected $casts = [
        'date_mouvement' => 'datetime',
        'montant' => 'decimal:2',
    ];

    // Relation avec le coffre
    public function coffre(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'coffre_id');
    }

    public function operateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }

    // Méthode helper pour obtenir le nom du coffre
    public function getNomCoffreAttribute()
    {
        return $this->coffre ? $this->coffre->nom : 'N/A';
    }
}