<?php
// app/Models/Coffre.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coffre extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 
        'devise', 
        'solde_actuel',
        'plafond',
        'statut',
        'description'
    ];

    protected $casts = [
        'solde_actuel' => 'decimal:2',
        'plafond' => 'decimal:2'
    ];

    public function mouvementsSource()
    {
        return $this->hasMany(MouvementCoffre::class, 'coffre_source_id');
    }

    public function mouvementsDestination()
    {
        return $this->hasMany(MouvementCoffre::class, 'coffre_destination_id');
    }

    public function rapports()
    {
        return $this->hasMany(RapportCoffre::class);
    }

    // Vérifier si le solde est suffisant
    public function soldeSuffisant($montant): bool
    {
        return $this->solde_actuel >= $montant;
    }

    // Débiter le coffre
    public function debiter($montant): void
    {
        if (!$this->soldeSuffisant($montant)) {
            throw new \Exception("Solde insuffisant dans le coffre. Solde actuel: {$this->solde_actuel}");
        }
        
        $this->solde_actuel -= $montant;
        $this->save();
    }

    // Créditer le coffre
    public function crediter($montant): void
    {
        $this->solde_actuel += $montant;
        $this->save();
    }
}