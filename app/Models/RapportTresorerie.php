<?php
// app/Models/RapportTresorerie.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class RapportTresorerie extends Model
{

      protected $table = 'rapport_tresoreries';
    protected $fillable = [
        'date_rapport',
        'numero_rapport',
        'total_depots',
        'total_retraits',
        'solde_total_caisses',
        'nombre_operations',
        'observations',
        'statut',
        'created_by'
    ];

    protected $casts = [
        'total_depots' => 'decimal:2',
        'total_retraits' => 'decimal:2',
        'solde_total_caisses' => 'decimal:2',
        'date_rapport' => 'date'
    ];

    public function detailsCaisses(): HasMany
    {
        return $this->hasMany(RapportTresorerieCaisse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rapport) {
            $rapport->numero_rapport = 'RAPP-' . now()->format('Ymd-His');
            $rapport->created_by = Auth::id();
        });
    }
}

// app/Models/RapportTresorerieCaisse.php
class RapportTresorerieCaisse extends Model
{
    protected $fillable = [
        'rapport_tresorerie_id',
        'caisse_id',
        'type_caisse',
        'solde_initial',
        'solde_final',
        'nombre_operations',
        'total_mouvements'
    ];

    protected $casts = [
        'solde_initial' => 'decimal:2',
        'solde_final' => 'decimal:2',
        'total_mouvements' => 'decimal:2'
    ];

    public function rapport(): BelongsTo
    {
        return $this->belongsTo(RapportTresorerie::class);
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }
}