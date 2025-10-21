<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Compte extends Model
{
    protected $fillable = [
        'numero_membre',
        'client_id',
        'groupe_solidaire_id',
        'numero_compte',
        'devise',
        'solde',
        'statut',
        'type_compte',
        'nom',
        'postnom',
        'prenom'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function groupeSolidaire()
    {
        return $this->belongsTo(GroupeSolidaire::class, 'groupe_solidaire_id');
    }

    public function mouvements()
    {
        return $this->hasMany(Mouvement::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class, 'compte_id');
    }

    protected $casts = [
        'solde' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($compte) {
            // Si c'est un compte individuel
            if ($compte->client_id && $compte->client) {
                $compte->numero_membre = $compte->client->numero_membre;
                $compte->nom = $compte->client->nom ?? '';
                $compte->postnom = $compte->client->postnom ?? '';
                $compte->prenom = $compte->client->prenom ?? '';
                $compte->type_compte = 'individuel';

                if (empty($compte->numero_compte)) {
                    $compte->numero_compte = self::generateUniqueCompteNumber('C');
                }
            }
            // Si c'est un compte de groupe
            elseif ($compte->groupe_solidaire_id && $compte->groupeSolidaire) {
                $compte->nom = $compte->groupeSolidaire->nom_groupe;
                $compte->type_compte = 'groupe_solidaire';

                if (empty($compte->numero_compte)) {
                    $compte->numero_compte = self::generateUniqueCompteNumber('GS');
                }
            }
        });
    }

    /**
     * Génère un numéro de compte unique
     */
    private static function generateUniqueCompteNumber($prefix)
    {
        $lastCompte = self::where('numero_compte', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCompte) {
            // Extraire le numéro du dernier compte
            $lastNumber = intval(substr($lastCompte->numero_compte, strlen($prefix)));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Formater le nouveau numéro
        $padding = $prefix === 'C' ? 5 : 6; // C00001 vs GS000001
        $newCompteNumber = $prefix . str_pad($newNumber, $padding, '0', STR_PAD_LEFT);

        // Vérifier que le numéro n'existe pas déjà (double sécurité)
        $exists = self::where('numero_compte', $newCompteNumber)->exists();
        
        if ($exists) {
            // Si le numéro existe déjà, on incrémente jusqu'à trouver un numéro libre
            do {
                $newNumber++;
                $newCompteNumber = $prefix . str_pad($newNumber, $padding, '0', STR_PAD_LEFT);
                $exists = self::where('numero_compte', $newCompteNumber)->exists();
            } while ($exists);
        }

        return $newCompteNumber;
    }

    // Scope pour filtrer les comptes par type
    public function scopeIndividuels($query)
    {
        return $query->where('type_compte', 'individuel');
    }

    public function scopeGroupesSolidaires($query)
    {
        return $query->where('type_compte', 'groupe_solidaire');
    }

    // Dans app/Models/Compte.php
    public function creditsGroupe()
    {
        return $this->hasMany(CreditGroupe::class, 'compte_id');
    }



/**
 * Mettre à jour le solde sans déclencher d'observers (méthode temporaire)
 */
public function updateSoldeSansObservers($nouveauSolde)
{
    // Utiliser DB raw pour éviter les observers
    DB::table('comptes')
        ->where('id', $this->id)
        ->update(['solde' => $nouveauSolde]);
    
    // Recharger le modèle
    $this->refresh();
}


}