<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompteTransitoire extends Model
{
    protected $fillable = ['user_id',
    'agent_nom',
    'devise',
    'solde',
    'statut'];

      public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    public function agent()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // créditer le compte transitoire
    public function credit(float $amount)
    {
        $this->solde = $this->solde + $amount;
        $this->save();
    }

    // débiter le compte transitoire (retourne false si solde insuffisant)
    public function debit(float $amount): bool
    {
        if ($this->solde < $amount) return false;
        $this->solde = $this->solde - $amount;
        $this->save();
        return true;
    }
}
