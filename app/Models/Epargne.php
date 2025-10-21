<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Compte;
use App\Models\CompteTransitoire;

class Epargne extends Model
{
    protected $fillable = [
        'groupe_solidaire_id',
        'client_id',
        'cycle_id',
        'user_id',
        'agent_nom',
        'montant',
        'date_apport ',
        'devise',
        'numero_compte_membre',
        'solde_apres_membre',
        'client_nom',
        'type_epargne',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function groupeSolidaire()
    {
        return $this->belongsTo(GroupeSolidaire::class, 'groupe_solidaire_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($epargne) {
            $cycle = Cycle::find($epargne->cycle_id);
            if (!$cycle) {
                throw new \Exception('Cycle introuvable');
            }

            // Déterminer le type d'épargne
            $isGroupe = !empty($epargne->groupe_solidaire_id);
            $epargne->type_epargne = $isGroupe ? 'groupe_solidaire' : 'individuel';

            if ($isGroupe) {
                // Pour les groupes
                $groupe = GroupeSolidaire::find($epargne->groupe_solidaire_id);
                $epargne->client_nom = $groupe ? $groupe->nom_groupe : 'Groupe Inconnu';
            } else {
                // Pour les clients individuels
                $client = Client::find($epargne->client_id);
                $epargne->client_nom = $client ? "{$client->nom} {$client->postnom} {$client->prenom}" : 'Inconnu';
            }

            $agent = User::find($epargne->user_id);
            $epargne->agent_nom = $agent ? $agent->name : 'Inconnu';

            // Date et devise
            $epargne->date_apport = $epargne->date_apport ?: now();
            $epargne->devise = $cycle->devise;

            // Statut initial
            $epargne->statut = 'en_attente_dispatch';

            // Compte transitoire de l'agent
            // $ct = CompteTransitoire::firstOrCreate(
            //     ['user_id' => $epargne->user_id, 'devise' => $epargne->devise],
            //     ['solde' => 0, 'statut' => 'actif', 'agent_nom' => $epargne->agent_nom]
            // );
            // $ct->solde += $epargne->montant;
            // $ct->save();

            // Gestion du compte selon le type (individuel ou groupe)
            if ($isGroupe) {
                // Pour les groupes : utiliser le compte du groupe
                $compte = Compte::where('groupe_solidaire_id', $epargne->groupe_solidaire_id)
                    ->where('devise', $epargne->devise)
                    ->where('type_compte', 'groupe_solidaire')
                    ->first();

                if (!$compte) {
                    throw new \Exception('Compte groupe solidaire introuvable');
                }
            } else {
                // Pour les clients individuels
                $compte = Compte::firstOrCreate(
                    ['client_id' => $epargne->client_id, 'devise' => $epargne->devise],
                    [
                        'solde' => 0, 
                        'numero_compte' => 'C'.str_pad($epargne->client_id, 6, '0', STR_PAD_LEFT),
                        'type_compte' => 'individuel'
                    ]
                );
            }

            // // Mettre à jour le solde du compte
            // $compte->solde += $epargne->montant;
            // $compte->save();

            // $epargne->numero_compte_membre = $compte->numero_compte;
            // $epargne->solde_apres_membre = $compte->solde;

            // SUPPRIMER la logique de premiere_mise
        });
    }

    public function user()
    {
        return $this->hasMany(\App\Models\User::class);
    }
}