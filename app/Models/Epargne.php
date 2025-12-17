<?php

namespace App\Models;

use App\Models\MouvementEpargne;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\Model;
use App\Models\Compte;
use App\Models\CompteTransitoire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Epargne extends Model
{
    protected $fillable = [
        'groupe_solidaire_id',
        'client_id',
        'cycle_id',
        'user_id',
        'agent_nom',
        'montant',
        'date_apport',
        'devise',
        'numero_compte_membre',
        'solde_apres_membre',
        'client_nom',
        'type_epargne',
        'statut',
    ];

    /**
     * Relation avec les écritures comptables
     */
    public function ecrituresComptables()
    {
        return $this->morphMany(EcritureComptable::class, 'sourceable');
    }

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

    public function collecteur()
    {
        return $this->belongsTo(User::class, 'agent_id');
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

            Log::info("Création épargne sans crédit compte transitoire", [
                'epargne_id' => $epargne->id,
                'agent_id' => $epargne->user_id,
                'montant' => $epargne->montant,
                'devise' => $epargne->devise
            ]);

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
                $compte = Compte::firstOrCreate(
                    [
                        'client_id' => $epargne->client_id, 
                        'devise' => $epargne->devise
                    ],
                    [
                        'solde' => 0, 
                        'numero_compte' => self::genererNumeroCompteParDevise($epargne->devise),
                        'type_compte' => 'individuel',
                        'nom' => $client->nom ?? '',
                        'postnom' => $client->postnom ?? '',
                        'prenom' => $client->prenom ?? '',
                        'numero_membre' => $client->numero_membre ?? null
                    ]
                );
            }
        });

        // MODIFIEZ CET ÉVÉNEMENT updated
        static::updated(function ($epargne) {
            // Vérifier si le statut a changé à "valide"
            if ($epargne->isDirty('statut') && $epargne->statut === 'valide') {
                Log::info("Épargne validée - traitement des mouvements", [
                    'epargne_id' => $epargne->id,
                    'nouveau_statut' => $epargne->statut
                ]);
                
                // Traiter l'épargne validée
                self::processEpargneValidee($epargne);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function compteEpargne()
    {
        return $this->belongsTo(CompteEpargne::class);
    }

    private static function genererNumeroCompteParDevise($devise)
    {
        $lastCompte = Compte::where('type_compte', 'individuel')
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        
        if ($lastCompte) {
            preg_match('/C(\d+)/', $lastCompte->numero_compte, $matches);
            if (isset($matches[1])) {
                $nextNumber = (int)$matches[1] + 1;
            }
        }

        return 'C' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Traiter une épargne validée :  et envoyer SMS
     */
private static function processEpargneValidee(Epargne $epargne)
{
    // NE RIEN FAIRE du tout !
    // Le dispatch est déjà géré par DispatchEpargnesTable::processDispatch
    
    Log::info("⚠️ Épargne déjà traitée par dispatch - aucune action nécessaire", [
        'epargne_id' => $epargne->id,
        'montant' => $epargne->montant,
        'devise' => $epargne->devise,
        'type' => $epargne->type_epargne,
        'statut' => $epargne->statut
    ]);
    
    // Juste vérifier que le solde est correct
    if ($epargne->type_epargne === 'individuel' && $epargne->client_id) {
        $client = Client::find($epargne->client_id);
        if ($client) {
            $compteEpargne = CompteEpargne::where('client_id', $client->id)
                ->where('devise', $epargne->devise)
                ->first();
            
            if ($compteEpargne) {
                $totalEpargnes = Epargne::where('client_id', $client->id)
                    ->where('statut', 'valide')
                    ->where('devise', $epargne->devise)
                    ->sum('montant');
                
                $totalRetraits = Mouvement::where('compte_epargne_id', $compteEpargne->id)
                    ->where('type', 'retrait')
                    ->sum('montant');
                
                $soldeTheorique = $totalEpargnes - $totalRetraits;
                
                if (abs($compteEpargne->solde - $soldeTheorique) > 0.01) {
                    Log::warning("⚠️ Incohérence détectée après validation", [
                        'compte_epargne_id' => $compteEpargne->id,
                        'solde_actuel' => $compteEpargne->solde,
                        'solde_theorique' => $soldeTheorique
                    ]);
                }
            }
        }
    }
}
  /**
 * Créer un mouvement d'épargne pour un client et mettre à jour le solde
 */
// Dans Epargne.php, modifiez juste cette partie :

// private static function creerMouvementEpargnePourClient(Epargne $epargne, Client $client, $montant)
// {
//     // Trouver ou créer le compte épargne
//     $compteEpargne = CompteEpargne::firstOrCreate(
//         [
//             'client_id' => $client->id,
//             'devise' => $epargne->devise,
//             'type_compte' => 'individuel'
//         ],
//         [
//             'solde' => 0,
//             'numero_compte' => CompteEpargne::genererNumeroCompteParDevise('individuel', $epargne->devise),
//             'statut' => 'actif',
//             'taux_interet' => 2.5,
//             'solde_minimum' => 0,
//             'conditions' => 'Compte épargne standard',
//             'date_ouverture' => now(),
//             'user_id' => Auth::id(),
//         ]
//     );

//     // CALCULER LE NOUVEAU SOLDE BASÉ SUR LES ÉPARGNES EXISTANTES
//     $totalEpargnes = Epargne::where('client_id', $client->id)
//         ->where('statut', 'valide')
//         ->where('devise', $epargne->devise)
//         ->sum('montant');
        
//     // CALCULER LES RETRAITS EXISTANTS
//     $totalRetraits = Mouvement::where('compte_epargne_id', $compteEpargne->id)
//         ->where('type', 'retrait')
//         ->sum('montant');
    
//     $nouveauSolde = $totalEpargnes - $totalRetraits;
    
//     // Créer le mouvement d'épargne
//     $mouvementEpargne = MouvementEpargne::create([
//         'compte_epargne_id' => $compteEpargne->id,
//         'epargne_id' => $epargne->id,
//         'user_id' => Auth::id(),
//         'type' => 'depot',
//         'montant' => $montant,
//         'solde_avant' => $compteEpargne->solde,
//         'solde_apres' => $nouveauSolde,
//         'devise' => $epargne->devise,
//         'reference' => 'DEP-EPG-' . $epargne->id . '-' . $client->id . '-' . time(),
//         'description' => "Dépôt épargne collecté par " . $epargne->agent_nom,
//         'statut' => 'completed',
//         'date_mouvement' => now(),
//         'operateur_nom' => $epargne->agent_nom,
//         'operateur_id' => $epargne->user_id,
//     ]);

//     // Mettre à jour le solde AVEC LA NOUVELLE VALEUR CALCULÉE
//     $compteEpargne->solde = $nouveauSolde;
//     $compteEpargne->save();

//     Log::info("✅ Mouvement épargne créé et solde synchronisé", [
//         'client_id' => $client->id,
//         'compte_epargne_id' => $compteEpargne->id,
//         'mouvement_id' => $mouvementEpargne->id,
//         'total_epargnes' => $totalEpargnes,
//         'total_retraits' => $totalRetraits,
//         'nouveau_solde' => $nouveauSolde
//     ]);
    
//     return $mouvementEpargne;
// }

  private static function mettreAJourSoldeEpargne(Epargne $epargne, Client $client, $montant)
{
    $compteEpargne = CompteEpargne::firstOrCreate(
        [
            'client_id' => $client->id,
            'devise' => $epargne->devise,
            'type_compte' => 'individuel'
        ],
        [
            'solde' => 0,
            'numero_compte' => CompteEpargne::genererNumeroCompteParDevise('individuel', $epargne->devise),
            'statut' => 'actif',
            'taux_interet' => 2.5,
            'solde_minimum' => 0,
            'conditions' => 'Compte épargne standard',
            'date_ouverture' => now(),
            'user_id' => Auth::id(),
        ]
    );

    // JUSTE mettre à jour le solde
    $soldeAvant = $compteEpargne->solde;
    $nouveauSolde = $soldeAvant + $montant;
    
    $compteEpargne->solde = $nouveauSolde;
    $compteEpargne->save();

    Log::info("✅ Solde épargne mis à jour (SANS mouvement)", [
        'client_id' => $client->id,
        'compte_epargne_id' => $compteEpargne->id,
        'solde_avant' => $soldeAvant,
        'montant' => $montant,
        'nouveau_solde' => $nouveauSolde
    ]);
}


// Ajoutez cette méthode dans le modèle CompteEpargne

/**
 * Synchroniser le solde avec les épargnes et retraits réels
 */
public function synchroniserSolde(): bool
{
    try {
        // 1. Calculer le total des épargnes VALIDES pour ce compte
        $totalEpargnes = 0;
        
        if ($this->type_compte === 'individuel' && $this->client_id) {
            $totalEpargnes = Epargne::where('client_id', $this->client_id)
                ->where('statut', 'valide')
                ->where('devise', $this->devise)
                ->sum('montant');
        } elseif ($this->type_compte === 'groupe_solidaire' && $this->groupe_solidaire_id) {
            $totalEpargnes = Epargne::where('groupe_solidaire_id', $this->groupe_solidaire_id)
                ->where('statut', 'valide')
                ->where('devise', $this->devise)
                ->sum('montant');
        }
        
        // 2. Calculer le total des retraits (dans la table mouvements)
        $totalRetraits = Mouvement::where('compte_epargne_id', $this->id)
            ->where('type', 'retrait')
            ->sum('montant');
        
        // 3. Calculer le solde théorique
        $soldeTheorique = $totalEpargnes - $totalRetraits;
        
        // 4. Vérifier l'écart avec le solde actuel
        $ecart = $this->solde - $soldeTheorique;
        
        if (abs($ecart) > 0.01) {
            Log::warning("❌ Écart détecté dans le solde du compte épargne", [
                'compte_id' => $this->id,
                'numero_compte' => $this->numero_compte,
                'solde_actuel' => $this->solde,
                'solde_theorique' => $soldeTheorique,
                'ecart' => $ecart,
                'total_epargnes' => $totalEpargnes,
                'total_retraits' => $totalRetraits
            ]);
            
            // Corriger le solde
            $ancienSolde = $this->solde;
            $this->solde = $soldeTheorique;
            $this->save();
            
            Log::info("✅ Solde corrigé pour {$this->numero_compte}", [
                'ancien_solde' => $ancienSolde,
                'nouveau_solde' => $soldeTheorique,
                'correction' => $ecart
            ]);
            
            return true;
        }
        
        return false;
        
    } catch (\Exception $e) {
        Log::error("❌ Erreur synchronisation solde", [
            'compte_id' => $this->id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

// Ajoutez aussi cette méthode pour afficher le solde calculé
public function getSoldeCalculeAttribute(): float
{
    $totalEpargnes = 0;
    
    if ($this->type_compte === 'individuel' && $this->client_id) {
        $totalEpargnes = Epargne::where('client_id', $this->client_id)
            ->where('statut', 'valide')
            ->where('devise', $this->devise)
            ->sum('montant');
    } elseif ($this->type_compte === 'groupe_solidaire' && $this->groupe_solidaire_id) {
        $totalEpargnes = Epargne::where('groupe_solidaire_id', $this->groupe_solidaire_id)
            ->where('statut', 'valide')
            ->where('devise', $this->devise)
            ->sum('montant');
    }
    
    $totalRetraits = Mouvement::where('compte_epargne_id', $this->id)
        ->where('type', 'retrait')
        ->sum('montant');
    
    return $totalEpargnes - $totalRetraits;
}


// Ajouter cette relation manquante
public function mouvementsEpargnes()
{
    return $this->hasMany(MouvementEpargne::class, 'compte_epargne_id');
}
}