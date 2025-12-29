<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'client_id', // AJOUTE CECI
        'telephone', 
        'message',
        'message_id',
        'status',
        'remarks',
        'uid',
        'response_data',
        'user_id',
        'transaction_id',
        'compte_id',
        'compte_epargne_id',
        'mouvement_id',
        'type',
        'sent_at',
        'delivery_status',
        'cost'
    ];

    protected $casts = [
        'response_data' => 'array',
        'sent_at' => 'datetime',
        'cost' => 'decimal:4'
    ];

    // Statuts possibles
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_PENDING = 'pending';
    const STATUS_UNDELIVERED = 'undeliverable';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    // CORRIGE CECI - change le nom de la méthode
    public function client(): BelongsTo // Était "clients()" au pluriel
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    
    public function compteEpargne(): BelongsTo
    {
        return $this->belongsTo(CompteEpargne::class, 'compte_epargne_id');
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(Mouvement::class);
    }
    
    public function getPhoneNumberAttribute()
    {
        return $this->telephone; 
    }

    public function getRecipientNameAttribute(): string
    {
        if ($this->client) {
            return $this->client->nom_complet;
        }
        
        if ($this->compte && $this->compte->client) {
            return $this->compte->client->nom_complet;
        }
        
        if ($this->compteEpargne) {
            return $this->compteEpargne->getNomCompletAttribute();
        }
        
        return 'N/A';
    }
    
    /**
     * Boot method pour gérer le téléphone automatiquement
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($smsLog) {
            // Si pas de téléphone mais un client_id est fourni
            if (empty($smsLog->telephone) && !empty($smsLog->client_id)) {
                $client = Client::find($smsLog->client_id);
                if ($client && !empty($client->telephone)) {
                    $smsLog->telephone = $client->telephone;
                }
            }
            
            // Si toujours pas de téléphone, mettre une valeur par défaut
            if (empty($smsLog->telephone)) {
                $smsLog->telephone = 'N/A';
            }
        });
    }
}