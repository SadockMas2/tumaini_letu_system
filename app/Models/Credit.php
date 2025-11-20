<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id',
         'agent_id', 
        'superviseur_id', 
        'type_credit',
        'montant_demande',
        'montant_accorde',
        'taux_interet',
        'montant_total',
        'frais_dossier',
        'frais_alerte',
        'frais_carnet',
        'frais_adhesion',
        'caution',
        'remboursement_hebdo',
        'duree_mois',
        'statut_demande',
        'motif_rejet',
        'date_demande',
        'date_octroi',
        'date_echeance',
        'credit_groupe_id'
    ];

    protected $casts = [
        'date_demande' => 'date',
        'date_octroi' => 'date',
        'date_echeance' => 'date',
        'montant_demande' => 'decimal:2',
        'montant_accorde' => 'decimal:2',
        'montant_total' => 'decimal:2',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function creditGroupe()
    {
        return $this->belongsTo(CreditGroupe::class);
    }

    public function paiements()
    {
        return $this->hasMany(PaiementCredit::class);
    }

    // Calcul des frais pour crédit groupe
public static function calculerFraisGroupe($montant)
{
    $frais = [
        50 => ['dossier' => 2, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 10],
        100 => ['dossier' => 4, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 20],
        150 => ['dossier' => 6, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 30],
        200 => ['dossier' => 8, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 40],
        250 => ['dossier' => 10, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 50],
        300 => ['dossier' => 12, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 60],
        350 => ['dossier' => 14, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 70],
        400 => ['dossier' => 16, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 80],
        450 => ['dossier' => 18, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 90],
        500 => ['dossier' => 20, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 100],
    ];

    $montantArrondi = floor($montant / 50) * 50;
    return $frais[$montantArrondi] ?? $frais[500];
}

// Dans la méthode calculerFraisIndividuel
public static function calculerFraisIndividuel($montant)
{
    $frais = [
        100 => ['dossier' => 4, 'alerte' => 4, 'caution' => 20],
        200 => ['dossier' => 8, 'alerte' => 4, 'caution' => 40],
        300 => ['dossier' => 12, 'alerte' => 4, 'caution' => 60],
        400 => ['dossier' => 16, 'alerte' => 4, 'caution' => 80],
        500 => ['dossier' => 20, 'alerte' => 4, 'caution' => 100],
        600 => ['dossier' => 24, 'alerte' => 4, 'caution' => 120],
        700 => ['dossier' => 28, 'alerte' => 4, 'caution' => 140],
        800 => ['dossier' => 32, 'alerte' => 4, 'caution' => 160],
        900 => ['dossier' => 36, 'alerte' => 4, 'caution' => 180],
        1000 => ['dossier' => 40, 'alerte' => 4, 'caution' => 200],
        1500 => ['dossier' => 60, 'alerte' => 4, 'caution' => 300],
        2000 => ['dossier' => 80, 'alerte' => 4, 'caution' => 400],
        2500 => ['dossier' => 100, 'alerte' => 4, 'caution' => 500],
        3000 => ['dossier' => 120, 'alerte' => 4, 'caution' => 600],
        3500 => ['dossier' => 140, 'alerte' => 4, 'caution' => 700],
        4000 => ['dossier' => 160, 'alerte' => 4, 'caution' => 800],
        4500 => ['dossier' => 180, 'alerte' => 4, 'caution' => 900],
        5000 => ['dossier' => 200, 'alerte' => 4, 'caution' => 1000],
    ];
    
    $montantArrondi = floor($montant / 100) * 100;
    if ($montantArrondi > 5000) $montantArrondi = 5000;
    
    $fraisCalcules = $frais[$montantArrondi] ?? $frais[100];
    
    // S'assurer que 'carnet' existe (toujours 0 pour individuel)
    if (!array_key_exists('carnet', $fraisCalcules)) {
        $fraisCalcules['carnet'] = 0;
    }
    
    return $fraisCalcules;
}

    // Calcul du montant total pour crédit groupe
    public static function calculerMontantTotalGroupe($montant)
    {
        return $montant * 1.225; // Coefficient 1.225
    }

    // Calcul du montant total pour crédit individuel
    public static function calculerMontantTotalIndividuel($montant)
    {
        if ($montant >= 100 && $montant <= 500) {
            return $montant * 0.308666 * 4;
        } elseif ($montant >= 501 && $montant <= 1000) {
            return $montant * 0.3019166667 * 4;
        } elseif ($montant >= 1001 && $montant <= 1599) {
            return $montant * 0.30866 * 4;
        } elseif ($montant >= 2000 && $montant <= 5000) {
            return $montant * 0.2985666667 * 4;
        }
        return $montant * 0.30 * 4; // Par défaut
    }

    // Calcul du remboursement hebdomadaire
    public static function calculerRemboursementHebdo($montantTotal, $typeCredit)
    {
        if ($typeCredit === 'groupe') {
            return $montantTotal / 16; // 4 mois = 16 semaines
        } else {
            return $montantTotal / 16; // Même calcul pour individuel
        }
    }

    // Calcul du total des frais
    public function getTotalFraisAttribute()
    {
        return $this->frais_dossier + $this->frais_alerte + $this->frais_carnet ;
    }

    // Dans App\Models\Credit.php
public static function boot()
{
    parent::boot();

    static::updated(function ($credit) {
        // Si le crédit est entièrement remboursé, vérifier le déblocage automatique
        if ($credit->montant_total <= 0 && $credit->getOriginal('montant_total') > 0) {
            Mouvement::debloquerCautionAutomatique($credit->compte_id);
        }
    });
}

// Dans App\Models\Credit

/**
 * Relation avec CompteSpecial (via l'historique)
 */
public function historiqueCompteSpecial()
{
    return $this->hasOne(HistoriqueCompteSpecial::class, 'credit_id');
}

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function superviseur()
    {
        return $this->belongsTo(User::class, 'superviseur_id');
    }
/**
 * Relation avec Mouvement pour les frais
 */
public function mouvementFrais()
{
    return $this->hasOne(Mouvement::class, 'credit_id')->where('type_mouvement', 'frais_payes_credit');
}
}