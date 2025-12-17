<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompteEpargne extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_compte',
        'client_id',
        'groupe_solidaire_id',
        'type_compte',
        'solde',
        'devise',
        'statut',
        'taux_interet',
        'solde_minimum',
        'conditions',
        'date_ouverture ',
        'user_id'
    ];

    protected $casts = [
        'solde' => 'float',
        'taux_interet' => 'float',
        'solde_minimum' => 'float',
        'date_ouverture' => 'date',
    ];

    /**
     * Relation avec le client (pour comptes individuels)
     */
    public function client(): BelongsTo
    { 
        return $this->belongsTo(Client::class);
    }

  
        public function mouvements()
        {
            return $this->hasMany(Mouvement::class, 'compte_epargne_id');
        }

    /**
     * Relation avec le groupe solidaire (pour comptes de groupe)
     */
    public function groupeSolidaire(): BelongsTo
    {
        return $this->belongsTo(GroupeSolidaire::class);
    }

    /**
     * Relation avec l'utilisateur qui a créé le compte
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot du modèle pour générer le numéro de compte automatiquement
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($compteEpargne) {
            if (empty($compteEpargne->numero_compte)) {
                $compteEpargne->numero_compte = self::genererNumeroCompte($compteEpargne->type_compte);
            }
            
            // Assigner automatiquement l'utilisateur connecté
            if (empty($compteEpargne->user_id) && Auth::check()) {
                $compteEpargne->user_id = Auth::id();
            }
        });
    }

    public static function genererNumeroCompteParDevise(string $typeCompte, string $devise): string
    {
        $prefix = $typeCompte === 'groupe_solidaire' ? 'CEG' : 'CEM';
        
        // Trouver le dernier numéro pour cette devise
        $lastCompte = self::where('devise', $devise)
            ->orderBy('id', 'desc')
            ->first();
            
        if ($lastCompte) {
            // Extraire le numéro du dernier compte (ex: "CEM000167" -> 167)
            preg_match('/'.$prefix.'(\d+)/', $lastCompte->numero_compte, $matches);
            $lastNumber = isset($matches[1]) ? (int)$matches[1] : 0;
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
            
        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }


    /**
     * Génère le numéro de compte selon le type
     */
    public static function genererNumeroCompte(string $typeCompte): string
    {
        $prefix = $typeCompte === 'groupe_solidaire' ? 'CEG' : 'CEM';
        
        // Trouver le dernier numéro pour ce type
        $lastCompte = self::where('type_compte', $typeCompte)
            ->orderBy('id', 'desc')
            ->first();
            
        $nextNumber = $lastCompte ? 
            (int) substr($lastCompte->numero_compte, 3) + 1 : 1;
            
        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Vérifie si le compte peut recevoir un dépôt
     */
    public function peutRecevoirDepot(float $montant): bool
    {
        return $this->statut === 'actif' && $montant > 0;
    }

    /**
     * Vérifie si le compte peut faire un retrait
     */
    public function peutFaireRetrait(float $montant): bool
    {
        return $this->statut === 'actif' 
            && $montant > 0 
            && $this->solde >= $montant 
            && ($this->solde - $montant) >= $this->solde_minimum;
    }

    /**
     * Créditer le compte
     */
    public function crediter(float $montant, string $description = ''): bool
    {
        if (!$this->peutRecevoirDepot($montant)) {
            return false;
        }

        $this->solde += $montant;
        return $this->save();
    }

    /**
     * Débiter le compte
     */
    public function debiter(float $montant, string $description = ''): bool
    {
        if (!$this->peutFaireRetrait($montant)) {
            return false;
        }

        $this->solde -= $montant;
        return $this->save();
    }

    public function getNomCompletAttribute(): string
{
    if ($this->type_compte === 'individuel' && $this->client) {
        return $this->client->nom_complet;
    } elseif ($this->type_compte === 'groupe_solidaire' && $this->groupeSolidaire) {
        return $this->groupeSolidaire->nom_groupe . ' (Groupe)';
    }
    
    return 'N/A';
}


/**
 * Vérifier et corriger les soldes des comptes épargne
 */
public static function verifierEtCorrigerSolder()
{
    $comptes = self::with(['mouvements' => function($query) {
        $query->where('statut', 'completed')
              ->orderBy('created_at');
    }])->get();

    $corrections = [];
    
    foreach ($comptes as $compte) {
        // Calculer le solde théorique à partir des mouvements
        $soldeTheorique = 0;
        $mouvements = $compte->mouvements;
        
        foreach ($mouvements as $mouvement) {
            if ($mouvement->type === 'depot') {
                $soldeTheorique += $mouvement->montant;
            } elseif ($mouvement->type === 'retrait') {
                $soldeTheorique -= $mouvement->montant;
            }
        }
        
        // Vérifier l'incohérence
        if (abs($compte->solde - $soldeTheorique) > 0.01) {
            $corrections[] = [
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numero_compte,
                'solde_actuel' => $compte->solde,
                'solde_theorique' => $soldeTheorique,
                'difference' => $compte->solde - $soldeTheorique
            ];
            
            // Corriger le solde
            $compte->solde = $soldeTheorique;
            $compte->save();
            
            Log::info("✅ Solde corrigé pour le compte {$compte->numero_compte}", [
                'ancien_solde' => $compte->solde,
                'nouveau_solde' => $soldeTheorique,
                'difference' => $compte->solde - $soldeTheorique
            ]);
        }
    }
    
    return $corrections;
}


public function synchroniserSolde(): bool
{
    try {
        // OPTION 1: Calculer à partir des épargnes ORIGINALES seulement
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
        
        // OPTION 2: OU calculer à partir des mouvements d'épargne seulement
        // $totalEpargnes = MouvementEpargne::where('compte_epargne_id', $this->id)
        //     ->where('type', 'depot')
        //     ->sum('montant');
        
        // Les retraits sont toujours dans la table mouvements
        $totalRetraits = Mouvement::where('compte_epargne_id', $this->id)
            ->where('type', 'retrait')
            ->sum('montant');
        
        $soldeTheorique = $totalEpargnes - $totalRetraits;
        
        $ecart = $this->solde - $soldeTheorique;
        
        if (abs($ecart) > 0.01) {
            Log::warning("❌ Écart détecté", [
                'compte_id' => $this->id,
                'numero_compte' => $this->numero_compte,
                'solde_actuel' => $this->solde,
                'solde_theorique' => $soldeTheorique,
                'ecart' => $ecart,
                'total_epargnes' => $totalEpargnes,
                'total_retraits' => $totalRetraits
            ]);
            
            return false; // Ne pas corriger automatiquement encore
        }
        
        return false;
        
    } catch (\Exception $e) {
        Log::error("❌ Erreur synchronisation", ['error' => $e->getMessage()]);
        return false;
    }
}
/**
 * Obtenir le solde calculé à partir des mouvements
 */
public function getSoldeCalculeAttribute(): float
{
    $totalEpargnes = 0;
    
    // FILTRER PAR DEVISE DU COMPTE
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
}