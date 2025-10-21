<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompteTransitoire extends Model
{
    protected $fillable = [
        'user_id',
        'agent_nom', 
        'devise',
        'solde',
        'statut'
    ];

    protected $attributes = [
        'solde' => 0,
        'statut' => 'actif',
        'devise' => 'USD'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Méthode statique pour obtenir ou créer le compte transitoire
    public static function getOrCreateForAgent($userId, $devise = 'USD')
    {
        $compte = self::where('user_id', $userId)
            ->where('devise', $devise)
            ->first();
        
        if (!$compte) {
            $user = User::find($userId);
            $compte = self::create([
                'user_id' => $userId,
                'agent_nom' => $user->name,
                'devise' => $devise,
                'solde' => 0,
                'statut' => 'actif'
            ]);
        }
        
        return $compte;
    }

    // créditer le compte transitoire
    public function credit(float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }
        
        $this->solde = $this->solde + $amount;
        return $this->save();
    }

    // débiter le compte transitoire (retourne false si solde insuffisant)
    public function debit(float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }
        
        if ($this->solde < $amount) {
            return false;
        }
        
        $this->solde = $this->solde - $amount;
        return $this->save();
    }

    // Vérifier si le solde est suffisant
    public function soldeSuffisant(float $amount): bool
    {
        return $this->solde >= $amount;
    }

    // Obtenir le solde formaté
    public function getSoldeFormateAttribute()
    {
        return number_format($this->solde, 2) . ' ' . $this->devise;
    }
}