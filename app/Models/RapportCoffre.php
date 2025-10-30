<?php
// app/Models/RapportCoffre.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RapportCoffre extends Model
{
    protected $fillable = [
        'coffre_fort_id',
        'date_rapport',
        'numero_rapport',
        'responsable_nom',
        'guichet_agence',
        'solde_ouverture',
        'total_entrees',
        'total_sorties',
        'solde_cloture_theorique',
        'solde_physique_reel',
        'ecart',
        'explication_ecart',
        'observations'
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
        return $this->belongsTo(CoffreFort::class);
    }

    public function getEntreesDetailsAttribute()
    {
        return $this->coffre->mouvements()
            ->where('type_mouvement', 'entree')
            ->whereDate('date_mouvement', $this->date_rapport)
            ->get();
    }

    public function getSortiesDetailsAttribute()
    {
        return $this->coffre->mouvements()
            ->where('type_mouvement', 'sortie')
            ->whereDate('date_mouvement', $this->date_rapport)
            ->get();
    }
}