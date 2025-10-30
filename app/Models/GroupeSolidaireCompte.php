<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupeSolidaireCompte extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_groupe',
        'code_groupe',
        'devise',
        'solde',
        'statut'
    ];

    // Relation avec les membres du groupe
    public function membres()
    {
        return $this->hasMany(GroupeSolidaire::class, 'groupe_solidaire_id');
    }

    // Relation avec les crédits groupe
    public function creditsGroupe()
    {
        return $this->hasMany(CreditGroupe::class, 'compte_id');
    }
}