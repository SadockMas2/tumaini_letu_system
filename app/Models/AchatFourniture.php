<?php
// app/Models/AchatFourniture.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AchatFourniture extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_achat',
        'quantite',
        'prix_unitaire',
        'montant_total',
        'devise',
        'fournisseur',
        'description',
        'operateur_id',
        'date_achat',
        'reference',
        'caisse_id', // Optionnel : si vous voulez lier à une caisse spécifique
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'date_achat' => 'date',
    ];

    /**
     * Relations
     */
    public function operateur()
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    /**
     * Scopes pour faciliter les requêtes
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date_achat', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('date_achat', now()->year)
                    ->whereMonth('date_achat', now()->month);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type_achat', $type);
    }

    public function scopeByDevise($query, $devise)
    {
        return $query->where('devise', $devise);
    }

    /**
     * Accesseurs
     */
    public function getTypeAchatFormattedAttribute()
    {
        return match($this->type_achat) {
            'carnet' => 'Carnets',
            'livre' => 'Livres',
            'autre' => 'Autres Articles',
            default => $this->type_achat
        };
    }

    public function getMontantTotalFormattedAttribute()
    {
        return number_format($this->montant_total, 2) . ' ' . $this->devise;
    }

    /**
     * Méthode statique pour générer une référence unique
     */
    public static function generateReference()
    {
        return 'ACH-' . now()->format('YmdHis');
    }

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($achat) {
            if (empty($achat->reference)) {
                $achat->reference = self::generateReference();
            }
        });
    }
}