<?php
// app/Models/CoffreFort.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoffreFort extends Model
{
    protected $fillable = [
        'nom_coffre',
        'devise',
        'solde_actuel',
        'responsable_id',
        'agence',
        'est_actif'
    ];

    protected $casts = [
        'solde_actuel' => 'decimal:2',
        'est_actif' => 'boolean'
    ];

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementCoffre::class);
    }

    public function rapports(): HasMany
    {
        return $this->hasMany(RapportCoffre::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function alimenter($montant, $provenance, $motif, $reference, $observations = null)
    {
        return $this->creerMouvement('entree', $montant, $provenance, $motif, $reference, $observations);
    }

    public function retirer($montant, $destination, $motif, $reference, $observations = null)
    {
        if ($this->solde_actuel < $montant) {
            throw new \Exception('Solde insuffisant dans le coffre');
        }

        return $this->creerMouvement('sortie', $montant, $destination, $motif, $reference, $observations);
    }

    private function creerMouvement($type, $montant, $provenanceDestination, $motif, $reference, $observations)
    {
        $mouvement = $this->mouvements()->create([
            'type_mouvement' => $type,
            'provenance_destination' => $provenanceDestination,
            'motif' => $motif,
            'reference' => $reference,
            'montant' => $montant,
            'user_id' => auth()->id(),
            'date_mouvement' => now(),
            'observations' => $observations
        ]);

        // Mettre à jour le solde
        $this->solde_actuel = (float) $this->solde_actuel;
        $montant = (float) $montant;

        if ($type === 'entree') {
            $this->solde_actuel += $montant;
        } else {
            $this->solde_actuel -= $montant;
        }
        $this->save();

        return $mouvement;
    }

    public function genererRapportQuotidien()
    {
        $date = now()->toDateString();
        
        $mouvementsJour = $this->mouvements()
            ->whereDate('date_mouvement', $date)
            ->get();

        $totalEntrees = $mouvementsJour->where('type_mouvement', 'entree')->sum('montant');
        $totalSorties = $mouvementsJour->where('type_mouvement', 'sortie')->sum('montant');

        // Calcul du solde d'ouverture
        $soldeOuverture = (float) $this->solde_actuel - (float) $totalEntrees + (float) $totalSorties;

        $rapport = $this->rapports()->create([
            'date_rapport' => $date,
            'numero_rapport' => 'RAPP-' . now()->format('Ymd') . '-' . $this->id,
            'responsable_nom' => $this->responsable->name,
            'guichet_agence' => $this->agence,
            'solde_ouverture' => $soldeOuverture,
            'total_entrees' => $totalEntrees,
            'total_sorties' => $totalSorties,
            'solde_cloture_theorique' => $this->solde_actuel,
            'solde_physique_reel' => $this->solde_actuel,
            'ecart' => 0
        ]);

        return $rapport;
    }
}