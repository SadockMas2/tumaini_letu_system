<?php
// app/Models/RapportCoffre.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MouvementCoffre extends Model
{
 protected $fillable = [
        'cash_register_id', // 
        'type_mouvement', // 'entree' ou 'sortie'
        'montant',
        'devise',
        'source_type', // 'banque', 'partenaire', etc.
        'destination_type', // 'comptabilite', etc.
        'reference',
        'description',
        'date_mouvement',
        'operateur_id'
    ];

    protected $casts = [
        'solde_ouverture' => 'decimal:2',
        'total_entrees' => 'decimal:2',
        'total_sorties' => 'decimal:2',
        'solde_cloture_theorique' => 'decimal:2',
        'solde_physique_reel' => 'decimal:2',
        'ecart' => 'decimal:2',
        'date_rapport' => 'date'
    ];

    public function coffre(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function entrees(): HasMany
    {
        return $this->hasMany(EntreeRapport::class);
    }

    public function sorties(): HasMany
    {
        return $this->hasMany(SortieRapport::class);
    }

    // Méthode pour générer le rapport quotidien
    public static function genererRapportQuotidien(int $coffreId, string $date = null, float $soldePhysique = null, string $observations = null): self
    {
        $date = $date ?: now()->format('Y-m-d');
        $coffre = CashRegister::findOrFail($coffreId);

        // Récupérer les mouvements de la journée
        $mouvements = MouvementCoffre::where(function($query) use ($coffreId) {
            $query->where('coffre_source_id', $coffreId)
                  ->orWhere('coffre_destination_id', $coffreId);
        })->whereDate('date_mouvement', $date)->get();

        $totalEntrees = $mouvements->where('type_mouvement', 'entree')->sum('montant');
        $totalSorties = $mouvements->where('type_mouvement', 'sortie')->sum('montant');

        $rapport = self::create([
            'coffre_id' => $coffreId,
            'date_rapport' => $date,
            'numero_rapport' => self::genererNumeroRapport($coffreId),
            'responsable_coffre' => $coffre->responsable->name,
            'agence' => $coffre->agence->nom_agence,
            'solde_ouverture' => $coffre->solde_ouverture,
            'total_entrees' => $totalEntrees,
            'total_sorties' => $totalSorties,
            'solde_cloture_theorique' => $coffre->solde_ouverture + $totalEntrees - $totalSorties,
            'solde_physique_reel' => $soldePhysique,
            'ecart' => $soldePhysique ? ($coffre->solde_ouverture + $totalEntrees - $totalSorties) - $soldePhysique : null,
            'observations' => $observations,
            'statut' => $soldePhysique ? 'finalise' : 'brouillon'
        ]);

        // Enregistrer les détails des entrées et sorties
        foreach ($mouvements as $mouvement) {
            if ($mouvement->type_mouvement === 'entree') {
                $rapport->entrees()->create([
                    'provenance' => $mouvement->source_type,
                    'motif' => $mouvement->description,
                    'reference' => $mouvement->reference,
                    'montant' => $mouvement->montant
                ]);
            } else {
                $rapport->sorties()->create([
                    'destination' => $mouvement->destination_type,
                    'motif' => $mouvement->description,
                    'reference' => $mouvement->reference,
                    'montant' => $mouvement->montant
                ]);
            }
        }

        // Si solde physique fourni, finaliser le rapport
        if ($soldePhysique) {
            $rapport->finaliser($soldePhysique, $observations);
        }

        return $rapport;
    }

    private static function genererNumeroRapport(int $coffreId): string
    {
        $count = self::where('coffre_id', $coffreId)->count() + 1;
        return 'RAPP-' . $coffreId . '-' . now()->format('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function finaliser(float $soldePhysique, string $observations = null): bool
    {
        $this->solde_physique_reel = $soldePhysique;
        $this->ecart = $this->solde_cloture_theorique - $soldePhysique;
        $this->observations = $observations;
        $this->statut = 'finalise';
        $this->save();

        // Mettre à jour le solde d'ouverture du coffre pour le lendemain
        $this->coffre->solde_ouverture = $soldePhysique;
        $this->coffre->save();

        return true;
    }
}