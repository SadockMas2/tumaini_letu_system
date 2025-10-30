<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coffre extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'devise', 'solde'];

    public function transactionsSource()
    {
        return $this->morphMany(Transaction::class, 'source');
    }

    public function transactionsDestination()
    {
        return $this->morphMany(Transaction::class, 'destination');
    }
}
