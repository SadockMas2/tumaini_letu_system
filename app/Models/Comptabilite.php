<?php
// app/Models/Comptabilite.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comptabilite extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'solde_disponible',
        'solde_bloque',
        'devise',
        'statut'
    ];

    protected $casts = [
        'solde_disponible' => 'decimal:2',
        'solde_bloque' => 'decimal:2'
    ];

    public function ecritures()
    {
        return $this->hasMany(EcritureComptable::class);
    }

    public function transfertsEntrants()
    {
        return $this->hasMany(TransfertComptable::class, 'comptabilite_destination_id');
    }

    public function transfertsSortants()
    {
        return $this->hasMany(TransfertComptable::class, 'comptabilite_source_id');
    }

    // Vérifier le solde disponible
    public function soldeDisponibleSuffisant($montant): bool
    {
        return $this->solde_disponible >= $montant;
    }

    // Bloquer des fonds pour distribution
    public function bloquerFonds($montant): void
    {
        if (!$this->soldeDisponibleSuffisant($montant)) {
            throw new \Exception("Solde disponible insuffisant. Solde disponible: {$this->solde_disponible}");
        }
        
        $this->solde_disponible -= $montant;
        $this->solde_bloque += $montant;
        $this->save();
    }

    // Débloquer des fonds
    public function debloquerFonds($montant): void
    {
        if ($this->solde_bloque < $montant) {
            throw new \Exception("Montant à débloquer supérieur au solde bloqué");
        }
        
        $this->solde_bloque -= $montant;
        $this->solde_disponible += $montant;
        $this->save();
    }

    // Distribuer aux caisses (débloque automatiquement)
    public function distribuerAuxCaisses($montant): void
    {
        if ($this->solde_bloque < $montant) {
            throw new \Exception("Fonds insuffisants bloqués pour distribution");
        }
        
        $this->solde_bloque -= $montant;
        $this->save();
    }

    // Recevoir un transfert
    public function recevoirTransfert($montant): void
    {
        $this->solde_disponible += $montant;
        $this->save();
    }
}