<?php

namespace App\Models;

use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TypePaiement;

class PaiementCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'credit_groupe_id', 
        'compte_id',
        'montant_paye',
        'date_paiement',
        'type_paiement',
        'reference',
        'statut',
        'capital_rembourse',
        'interets_payes'
    ];

    protected $casts = [
        'date_paiement' => 'datetime',
        'montant_paye' => 'decimal:2',
        'capital_rembourse' => 'decimal:2',
        'interets_payes' => 'decimal:2',
        'type_paiement' => TypePaiement::class,
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    // AJOUT: Relation avec CreditGroupe
    public function creditGroupe()
{
    // Si la colonne existe
    if (Schema::hasColumn('paiement_credits', 'credit_groupe_id')) {
        return $this->belongsTo(CreditGroupe::class, 'credit_groupe_id');
    }
    
    // Sinon utiliser compte_id
    return $this->belongsTo(CreditGroupe::class, 'compte_id', 'compte_id');
}
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function mouvement()
    {
        return $this->hasOne(Mouvement::class, 'reference', 'reference');
    }
    
}