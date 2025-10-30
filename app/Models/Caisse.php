<?php
// app/Models/Caisse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caisse extends Model
{
    protected $fillable = [
        'type_caisse',
        'devise',
        'solde_actuel',
        'plafond',
        'comptable_id',
        'agence_id',
        'statut',
        'description'
    ];

    protected $casts = [
        'solde_actuel' => 'decimal:2',
        'plafond' => 'decimal:2'
    ];

    public function comptable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comptable_id');
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class);
    }

    public function effectuerDepense(float $montant, array $details): Depense
    {
        if ($this->type_caisse === 'petite_caisse' && $montant > 100) {
            throw new \Exception('La petite caisse ne peut pas gérer des dépenses supérieures à 100 USD');
        }

        if ($this->solde_actuel < $montant) {
            throw new \Exception('Solde insuffisant dans la caisse');
        }

        $this->solde_actuel -= $montant;
        $this->save();

        return Depense::create(array_merge($details, [
            'caisse_id' => $this->id,
            'montant' => $montant,
            'devise' => $this->devise,
            'date_depense' => now(),
            'created_by' => auth()->id(),
            'reference' => 'DEP-' . now()->format('YmdHis')
        ]));
    }

    public function recevoirAlimentation(float $montant, string $reference): void
    {
        $this->solde_actuel += $montant;
        $this->save();
    }
}