<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompteBancaire extends Model
{
    use HasFactory;

    protected $table = 'comptes_bancaires';

    protected $fillable = ['nom_banque',
                            'numero_compte',
                            'devise',
                             'solde'];

    public function transactionsSource()
    {
        return $this->morphMany(Transaction::class, 'source');
    }

    public function transactionsDestination()
    {
        return $this->morphMany(Transaction::class, 'destination');
    }
}

