<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class MouvementEpargne extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_epargne_id',
        'epargne_id',
        'type',
        'montant',
        'solde_avant',
        'solde_apres',
        'devise',
        'description',
        'reference',
        'operateur_nom',
        'operateur_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'solde_avant' => 'decimal:2',
        'solde_apres' => 'decimal:2',
        'date_mouvement' => 'datetime',
    ];

    /**
     * Relation avec le compte épargne
     */
    public function compteEpargne(): BelongsTo
    {
        return $this->belongsTo(CompteEpargne::class);
    }

    /**
     * Relation avec l'épargne source
     */
    public function epargne(): BelongsTo
    {
        return $this->belongsTo(Epargne::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le client via compte épargne
     */
    public function client()
    {
        return $this->compteEpargne?->client;
    }

    /**
     * Obtenir le type formaté
     */
    public function getTypeFormattedAttribute(): string
    {
        return match($this->type) {
            'depot' => 'Dépôt',
            'retrait' => 'Retrait',
            default => $this->type,
        };
    }

    /**
     * Obtenir le statut formaté
     */
    public function getStatutFormattedAttribute(): string
    {
        return match($this->statut) {
            'pending' => 'En attente',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => $this->statut,
        };
    }

    // SUPPRIMEZ TOUT LE BOOT OU METTEZ-LE SIMPLEMENT COMME ÇA :
    protected static function boot()
    {
        parent::boot();
        
        // NE FAITES RIEN ICI - L'Observateur s'occupe des SMS
        // static::created(function ($mouvementEpargne) {
        //     // L'Observateur MouvementEpargneObserver gère les SMS
        // });
    }
    
    // SUPPRIMEZ CES DEUX MÉTHODES AUSSI :
    // private static function triggerSmsNotification(MouvementEpargne $mouvement)
    // {
    //     ...
    // }
    
    // private static function formatMessage(MouvementEpargne $mouvement, string $clientName): string
    // {
    //     ...
    // }
}