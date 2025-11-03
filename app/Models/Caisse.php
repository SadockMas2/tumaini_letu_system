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
        'solde', // Utilisez 'solde' au lieu de 'solde_actuel'
        'plafond',
        'nom', // Utilisez 'nom' au lieu de 'description'
        'statut',
        'comptable_id',
        'agence_id'
    ];

    protected $casts = [
        'solde' => 'decimal:2', // CHANGÉ : solde_actuel → solde
        'plafond' => 'decimal:2'
    ];

    // Accesseur pour compatibilité
    public function getDescriptionAttribute()
    {
        return $this->nom; // Utilisez 'nom' comme description
    }

    // Mutateur pour compatibilité
    public function setDescriptionAttribute($value)
    {
        $this->attributes['nom'] = $value;
    }

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

        if ($this->solde < $montant) { // CHANGÉ : solde_actuel → solde
            throw new \Exception('Solde insuffisant dans la caisse');
        }

        $this->solde -= $montant; // CHANGÉ : solde_actuel → solde
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
        $this->solde += $montant; // CHANGÉ : solde_actuel → solde
        $this->save();
    }

    public function getTypeCaisseFormattedAttribute(): string
    {
        return match($this->type_caisse) {
            'petite_caisse' => 'Petite Caisse (< 100 USD)',
            'grande_caisse' => 'Grande Caisse',
            'caisse_operations' => 'Caisse Opérations',
            default => $this->type_caisse
        };
    }
}