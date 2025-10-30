<?php
// app/Models/CompteGroupe.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompteGroupe extends Model
{
    protected $table = 'comptes_groupes';

    protected $fillable = [
        'groupe_solidaire_id',
        'numero_compte',
        'devise',
        'solde',
        'statut',
    ];

    protected $casts = [
        'solde' => 'decimal:2'
    ];

    public function groupeSolidaire(): BelongsTo
    {
        return $this->belongsTo(GroupeSolidaire::class);
    }

    public function mouvements()
    {
        return $this->hasMany(MouvementGroupe::class, 'compte_groupe_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($compteGroupe) {
            if (!$compteGroupe->numero_compte) {
                // Générer le numéro de compte G00001, G00002...
                $lastCompte = self::where('numero_compte', 'LIKE', 'GS%')
                ->lastest('id') 
               ->first();

               if ($lastCompte) {
                     $lastNumber = intval(substr($lastCompte->numero_compte, 2));
                     $newNumber = $lastNumber + 1;
                } else {
                     $lastNumber = 1;
                }
                 $newNumber = $lastNumber + 1;
                 $compteGroupe->numero_compte = 'GS' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);


            }
        });
    }
}