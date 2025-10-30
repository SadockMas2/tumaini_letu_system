<?php
// app/Models/CashRegister.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'nom',
        'devise',
        'solde_actuel',
        'solde_ouverture',
        'solde_cloture',
        'agence_id',
        'responsable_id',
        'statut',
        'plafond_journalier',
        'description'
    ];

    protected $casts = [
        'solde_actuel' => 'decimal:2',
        'solde_ouverture' => 'decimal:2',
        'solde_cloture' => 'decimal:2',
        'plafond_journalier' => 'decimal:2'
    ];

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function rapportsCoffre(): HasMany
    {
        return $this->hasMany(RapportCoffre::class);
    }

    public function alimenter(float $montant, string $source, string $reference): MouvementCoffre
    {
        $this->solde_actuel += $montant;
        $this->save();

        return MouvementCoffre::create([
            'coffre_destination_id' => $this->id,
            'type_mouvement' => 'entree',
            'montant' => $montant,
            'devise' => $this->devise,
            'source_type' => $source,
            'reference' => $reference,
            'description' => "Alimentation depuis {$source}",
            'date_mouvement' => now(),
            'operateur_id' => auth()->id()
        ]);
    }

    public function transfererVersCaisse(float $montant, string $typeCaisse, string $reference): MouvementCoffre
    {
        if ($this->solde_actuel < $montant) {
            throw new \Exception('Solde insuffisant dans le coffre');
        }

        $this->solde_actuel -= $montant;
        $this->save();

        return MouvementCoffre::create([
            'coffre_source_id' => $this->id,
            'type_mouvement' => 'sortie',
            'montant' => $montant,
            'devise' => $this->devise,
            'destination_type' => $typeCaisse,
            'reference' => $reference,
            'description' => "Transfert vers {$typeCaisse}",
            'date_mouvement' => now(),
            'operateur_id' => auth()->id()
        ]);
    }

    public function peutTransferer(float $montant): bool
    {
        return $this->solde_actuel >= $montant;
    }
}