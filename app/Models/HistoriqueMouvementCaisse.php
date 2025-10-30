<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueMouvementCaisse extends Model
{
    protected $table = 'historique_mouvement_caisses';
    
    protected $fillable = [
        'date_cloture',
        'total_depots',
        'total_retraits',
        'solde_final',
        'nombre_operations',
        'cloture_par',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'cloture_par');
    }
}