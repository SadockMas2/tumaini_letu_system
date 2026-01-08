<?php
// app/Models/JournalComptable.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalComptable extends Model
{
    protected $fillable = [
        'code_journal',
        'libelle_journal',
        'type_journal',
        'agence_id',
        'responsable_id',
        'date_ouverture',
        'date_fermeture',
        'solde_initial',
        'solde_final',
        'statut'
    ];

    protected $casts = [
        'solde_initial' => 'decimal:2',
        'solde_final' => 'decimal:2',
        'date_ouverture' => 'date',
        'date_fermeture' => 'date'
    ];

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

     public function ecritures_comptables(): HasMany
    {
        return $this->hasMany(EcritureComptable::class, 'journal_comptable_id');
    }

    public function ecritures(): HasMany
    {
        return $this->hasMany(EcritureComptable::class);
    }
}