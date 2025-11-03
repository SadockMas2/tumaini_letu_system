<?php
// app/Models/CashRegister.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'nom',
        'devise', 
        'solde_actuel',
        'solde_ouverture',
        'plafond',
        'responsable_id',
        'agence_id',
        'statut'
    ];

    protected $casts = [
        'solde_actuel' => 'decimal:2',
        'solde_ouverture' => 'decimal:2',
        'plafond' => 'decimal:2'
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementCoffre::class, 'coffre_id');
    }

    public function alimenter(float $montant, string $source, string $reference, string $description = null): MouvementCoffre
    {
        $this->solde_actuel += $montant;
        $this->save();

        return MouvementCoffre::create([
            'coffre_id' => $this->id,
            'type_mouvement' => 'entree',
            'montant' => $montant,
            'devise' => $this->devise,
            'source_type' => $source,
            'reference' => $reference,
            'description' => $description ?? "Alimentation depuis {$source}",
            'date_mouvement' => now(),
            'operateur_id' => auth()->id()
        ]);
    }

    public function transfererVersComptabilite(float $montant, string $motif): MouvementCoffre
    {
        if ($this->solde_actuel < $montant) {
            throw new \Exception('Solde insuffisant dans le coffre');
        }

        $this->solde_actuel -= $montant;
        $this->save();

        return MouvementCoffre::create([
            'coffre_id' => $this->id,
            'type_mouvement' => 'sortie',
            'montant' => $montant,
            'devise' => $this->devise,
            'destination_type' => 'comptabilite',
            'reference' => 'TRANSF-COMPT-' . now()->format('YmdHis'),
            'description' => "Transfert vers comptabilité - {$motif}",
            'date_mouvement' => now(),
            'operateur_id' => auth()->id()
        ]);
    }
}