<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MouvementCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'role_source',
        'role_dest',
        'montant',
        'statut',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }
}
