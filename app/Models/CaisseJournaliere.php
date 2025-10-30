<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaisseJournaliere extends Model
{
    use HasFactory;

    protected $table = 'caisse_journalieres';

    protected $fillable = ['nom_caisse', 'devise', 'solde'];

    public function transactionsSource()
    {
        return $this->morphMany(Transaction::class, 'source');
    }

    public function transactionsDestination()
    {
        return $this->morphMany(Transaction::class, 'destination');
    }

    
}

