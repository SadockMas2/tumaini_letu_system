<?php
// app/Models/TauxChange.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TauxChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'devise_source',
        'devise_destination',
        'taux',
        'date_effet',
        'est_actif',
        'created_by'
    ];

    protected $casts = [
        'taux' => 'decimal:4',
        'date_effet' => 'datetime',
        'est_actif' => 'boolean'
    ];

    /**
     * Récupère le taux de change actuel entre deux devises
     */
    public static function getTauxActuel($deviseSource, $deviseDestination)
    {
        return self::where('devise_source', $deviseSource)
                  ->where('devise_destination', $deviseDestination)
                  ->where('est_actif', true)
                  ->where('date_effet', '<=', now())
                  ->orderBy('date_effet', 'desc')
                  ->first();
    }

    /**
     * Récupère tous les taux de change actifs
     */
    public static function getTauxActifs()
    {
        return self::where('est_actif', true)
                  ->where('date_effet', '<=', now())
                  ->orderBy('date_effet', 'desc')
                  ->get();
    }

    /**
     * Relation avec l'utilisateur qui a créé le taux
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}