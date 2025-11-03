<?php
// app/Models/EcritureComptable.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcritureComptable extends Model
{
    protected $fillable = [
        'journal_comptable_id',
        'reference_operation',
        'type_operation',
        'compte_number',
        'libelle',
        'montant_debit',
        'montant_credit',
        'date_ecriture',
        'date_valeur',
        'devise',
        'statut',
        'notes',
        'piece_jointe',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'montant_debit' => 'decimal:2',
        'montant_credit' => 'decimal:2',
        'date_ecriture' => 'datetime',
        'date_valeur' => 'datetime'
    ];

    // Désactiver les timestamps automatiques
    public $timestamps = false;

    // Relation avec le journal comptable
    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalComptable::class, 'journal_comptable_id');
    }

    // Relation avec le plan comptable
    public function planComptable(): BelongsTo
    {
        return $this->belongsTo(PlanComptable::class, 'compte_number', 'numero_compte');
    }

    // Relation avec le créateur
    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relation avec le modificateur
    public function modificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Boot method pour gérer created_by et updated_by
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}