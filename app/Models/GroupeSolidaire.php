<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupeSolidaire extends Model
{
    use HasFactory;

    protected $table = 'groupes_solidaires';

    protected $fillable = [
        'numero_groupe',
        'nom_groupe', 
        'numero_cycle',
        'adresse',
        'date_debut_cycle',
        'date_fin_cycle',
    ];

    public function membres()
    {
        return $this->belongsToMany(Client::class, 'groupes_membres', 'groupe_solidaire_id', 'client_id');
    }

    public function comptes()
    {
        return $this->hasMany(Compte::class, 'groupe_solidaire_id');
    }

    public function compteUSD()
    {
        return $this->hasOne(Compte::class, 'groupe_solidaire_id')->where('devise', 'USD');
    }

    public function compteCDF()
    {
        return $this->hasOne(Compte::class, 'groupe_solidaire_id')->where('devise', 'CDF');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($groupe) {
            if (!$groupe->numero_groupe) {
                // Génère le numéro de groupe commençant à 3000001
                $last = self::max('numero_groupe') ?? 300000;
                $groupe->numero_groupe = $last + 1;
            }
        });

        static::created(function ($groupe) {
            $groupe->creerComptesGroupes();
        });
    }

    // public function creerComptesGroupes()
    // {
    //     // Créer le compte USD
    //     Compte::create([
    //         'groupe_solidaire_id' => $this->id,
    //         'numero_compte' => $this->genererNumeroCompteGroupe('USD'),
    //         'nom' => $this->nom_groupe . ' - USD',
    //         'devise' => 'USD',
    //         'solde' => 0,
    //         'statut' => 'actif',
    //         'type_compte' => 'groupe_solidaire'
    //     ]);

    //     // Créer le compte CDF
    //     Compte::create([
    //         'groupe_solidaire_id' => $this->id,
    //         'numero_compte' => $this->genererNumeroCompteGroupe('CDF'),
    //         'nom' => $this->nom_groupe . ' - CDF', 
    //         'devise' => 'CDF',
    //         'solde' => 0,
    //         'statut' => 'actif',
    //         'type_compte' => 'groupe_solidaire'
    //     ]);
    // }

    public function hascomptes($devise): bool
    {
        return $this->comptes()->where('devise', $devise)->exists();
    }

    public function getComptesGroupes()
    {
        return $this->comptes()->where('type_compte', 'groupe_solidaire')->get();
    }
  

    private function genererNumeroCompteGroupe($devise)
    {
        // Cherche le dernier numéro de compte groupe (GS000001, GS000002, etc.)
        $lastCompte = Compte::where('type_compte', 'groupe_solidaire')
            ->where('numero_compte', 'LIKE', 'GS%')
            ->latest('id')
            ->first();
        
        if ($lastCompte) {
            $lastNumber = intval(substr($lastCompte->numero_compte, 2));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1; // Commence à GS000001
        }
        
        return 'GS' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
}