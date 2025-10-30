<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'groupe_solidaire_id',
        'client_nom',
        'numero_cycle',
        'date_debut',
        'date_fin',
        'devise',
        'solde_initial',
        'statut',
        'type_cycle',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function groupeSolidaire()
    {
        return $this->belongsTo(GroupeSolidaire::class, 'groupe_solidaire_id');
    }

    public function epargnes()
    {
        return $this->hasMany(Epargne::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($cycle) {
            // Pour les cycles individuels
            if ($cycle->client_id) {
                $client = Client::find($cycle->client_id);
                $cycle->client_nom = $client ? "{$client->nom} {$client->postnom} {$client->prenom}" : 'Inconnu';
                $cycle->type_cycle = 'individuel';

                // Déterminer le numéro du cycle automatiquement
                $dernierCycle = self::where('client_id', $cycle->client_id)
                    ->orderBy('numero_cycle', 'desc')
                    ->first();
                $cycle->numero_cycle = $dernierCycle ? $dernierCycle->numero_cycle + 1 : 1;
            }
            // Pour les cycles de groupe
            elseif ($cycle->groupe_solidaire_id) {
                $groupe = GroupeSolidaire::find($cycle->groupe_solidaire_id);
                $cycle->client_nom = $groupe ? $groupe->nom_groupe : 'Groupe Inconnu';
                $cycle->type_cycle = 'groupe_solidaire';

                // Déterminer le numéro du cycle automatiquement pour le groupe
                $dernierCycle = self::where('groupe_solidaire_id', $cycle->groupe_solidaire_id)
                    ->orderBy('numero_cycle', 'desc')
                    ->first();
                $cycle->numero_cycle = $dernierCycle ? $dernierCycle->numero_cycle + 1 : 1;
            }

            // Définir le statut initial
            $cycle->statut = 'ouvert';

            // Si devise ou solde initial non fournis, prendre des valeurs par défaut
            $cycle->devise = $cycle->devise ?: 'CDF';
            $cycle->solde_initial = $cycle->solde_initial ?: 0;
        });

        // SUPPRIMER l'ancien code qui créditait le compte spécial ici
        // Le crédit du compte spécial se fera uniquement via le CycleService
    }

    // Fermer le cycle
    public function fermer()
    {
        $this->statut = 'cloture';
        $this->date_fin = now();
        $this->save();
    }

    // Scope pour filtrer par type
    public function scopeIndividuels($query)
    {
        return $query->where('type_cycle', 'individuel');
    }

    public function scopeGroupesSolidaires($query)
    {
        return $query->where('type_cycle', 'groupe_solidaire');
    }

    // Méthode pour créditer le compte spécial (à appeler explicitement)
    public function crediterCompteSpecial()
    {
        if ($this->solde_initial > 0) {
            $compteSpecial = CompteSpecial::firstOrCreate(
                ['devise' => $this->devise],
                [
                    'nom' => 'Compte Spécial ' . $this->devise,
                    'solde' => 0
                ]
            );
            $compteSpecial->solde += $this->solde_initial;
            $compteSpecial->save();
        }
    }
}