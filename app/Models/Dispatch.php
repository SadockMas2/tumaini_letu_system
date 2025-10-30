<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{
    protected $fillable = [
        'user_id',
        'agent_id',
        'agent_nom',
        'devise',
        'montant_total',
        'notes',
        'statut'];

    public function caissier()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function agent()
    {
        return $this->belongsTo(\App\Models\User::class, 'agent_id');
    }

    
}
