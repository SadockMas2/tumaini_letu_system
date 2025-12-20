<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

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
        'user_id',
        'agent_nom',
        'nombre_max_epargnes', 
        'nombre_epargnes_actuel', 
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function ecrituresComptables()
{
    return $this->morphMany(EcritureComptable::class, 'sourceable');
}

    public function groupeSolidaire()
    {
        return $this->belongsTo(GroupeSolidaire::class, 'groupe_solidaire_id');
    }

    public function epargnes()
    {
        return $this->hasMany(Epargne::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    /**
 * Vérifier et clôturer automatiquement si la limite est atteinte
 */
public function verifierEtCloturer(): void
{
    $count = $this->epargnes()->count();
    $max = $this->nombre_max_epargnes ?? 30;
    
    if ($count >= $max && $this->statut !== 'clôturé') {
        $this->update([
            'statut' => 'clôturé',
            'date_cloture' => now(),
            'solde_final' => $this->solde_initial + $this->epargnes()->where('statut', 'valide')->sum('montant')
        ]);
        
        Log::info("Cycle clôturé automatiquement - limite d'épargnes atteinte", [
            'cycle_id' => $this->id,
            'epargnes' => $count,
            'limite' => $max
        ]);
    }
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

                // ✅ NOUVEAU : Déterminer le numéro du cycle par devise
                $dernierCycle = self::where('client_id', $cycle->client_id)
                    ->where('devise', $cycle->devise) // Filtrer par devise
                    ->orderBy('numero_cycle', 'desc')
                    ->first();
                $cycle->numero_cycle = $dernierCycle ? $dernierCycle->numero_cycle + 1 : 1;
            }
            // Pour les cycles de groupe
            elseif ($cycle->groupe_solidaire_id) {
                $groupe = GroupeSolidaire::find($cycle->groupe_solidaire_id);
                $cycle->client_nom = $groupe ? $groupe->nom_groupe : 'Groupe Inconnu';
                $cycle->type_cycle = 'groupe_solidaire';

                // ✅ NOUVEAU : Déterminer le numéro du cycle par devise
                $dernierCycle = self::where('groupe_solidaire_id', $cycle->groupe_solidaire_id)
                    ->where('devise', $cycle->devise) // Filtrer par devise
                    ->orderBy('numero_cycle', 'desc')
                    ->first();
                $cycle->numero_cycle = $dernierCycle ? $dernierCycle->numero_cycle + 1 : 1;
            }

            // Le reste de votre code existant reste inchangé...
            $cycle->statut = 'ouvert';
            $cycle->devise = $cycle->devise ?: 'CDF';
            $cycle->solde_initial = $cycle->solde_initial ?: 0;
            $cycle->nombre_max_epargnes = $cycle->nombre_max_epargnes ?: 30;
            $cycle->nombre_epargnes_actuel = 0;
        });

          static::created(function ($epargne) {
        // Incrémenter le compteur d'épargnes
        $cycle = $epargne->cycle;
        if ($cycle) {
            $cycle->synchroniserCompteurEpargnes();
            $cycle->verifierEtCloturer();
        }
    });

    }

     /**
     * Vérifier si le cycle peut accepter de nouvelles épargnes
     */
    public function peutAccepterEpargne(): bool
    {
        return $this->statut === 'ouvert' && 
               $this->nombre_epargnes_actuel < $this->nombre_max_epargnes;
    }

    // Dans votre modèle Cycle, ajoutez cette méthode
    public function scopeParDevise($query, string $devise)
    {
        return $query->where('devise', $devise);
    }

    /**
     * Incrémenter le compteur d'épargnes et clôturer si nécessaire
     */
    public function incrementerEpargne(): void
    {
        $this->nombre_epargnes_actuel += 1;
        
        // Clôturer automatiquement si le maximum est atteint
        if ($this->nombre_epargnes_actuel >= $this->nombre_max_epargnes) {
            $this->fermer();
            Log::info("Cycle clôturé automatiquement - limite d'épargnes atteinte", [
                'cycle_id' => $this->id,
                'nombre_epargnes' => $this->nombre_epargnes_actuel,
                'limite' => $this->nombre_max_epargnes
            ]);
        } else {
            $this->save();
        }
    }

    /**
     * Obtenir le nombre d'épargnes valides
     */
       public function getNombreEpargnesValidesAttribute(): int
    {
        return $this->epargnes()->where('statut', 'valide')->count();
    }

    public function getNombreEpargnesReelAttribute(): int
    {
        return $this->epargnes()->count();
    }






    /**
     * Obtenir le nombre d'épargnes restantes possibles
     */
       public function getEpargnesRestantesAttribute(): int
{
    $epargnesExistantes = $this->getNombreEpargnesReelAttribute();
    return max(0, $this->nombre_max_epargnes - $epargnesExistantes);
}

public function synchroniserCompteurEpargnes(): void
{
    $nombreReel = $this->getNombreEpargnesReelAttribute();
    $this->update(['nombre_epargnes_actuel' => $nombreReel]);
}

/**
 * Obtenir le nombre d'épargnes valides
 */

/**
 * Méthode pour fermer le cycle avec historique
 */
public function fermer(): void
{
    $this->update([
        'statut' => 'clôturé',
        'date_cloture' => now(),
        'solde_final' => $this->solde_initial + $this->epargnes()->where('statut', 'valide')->sum('montant')
    ]);
    
    // Historique de clôture
    Log::info("Cycle clôturé", [
        'cycle_id' => $this->id,
        'solde_final' => $this->solde_final,
        'date_cloture' => $this->date_cloture
    ]);
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

    // Méthode pour créditer le compte spécial
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