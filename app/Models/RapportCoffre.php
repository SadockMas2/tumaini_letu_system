<?php
// app/Models/RapportCoffre.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RapportCoffre extends Model
{
    protected $fillable = [
        'coffre_id',
        'date_rapport',
        'numero_rapport',
        'responsable_coffre',
        'agence',
        'solde_ouverture',
        'total_entrees',
        'total_sorties',
        'solde_cloture_theorique',
        'solde_physique_reel',
        'ecart',
        'observations',
        'statut'
    ];

    protected $casts = [
        'solde_ouverture' => 'decimal:2',
        'total_entrees' => 'decimal:2',
        'total_sorties' => 'decimal:2',
        'solde_cloture_theorique' => 'decimal:2',
        'solde_physique_reel' => 'decimal:2',
        'ecart' => 'decimal:2',
        'date_rapport' => 'date'
    ];

    public function coffre(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'coffre_id');
    }

    public function entrees(): HasMany
    {
        return $this->hasMany(EntreeRapport::class);
    }

    public function sorties(): HasMany
    {
        return $this->hasMany(SortieRapport::class);
    }
}