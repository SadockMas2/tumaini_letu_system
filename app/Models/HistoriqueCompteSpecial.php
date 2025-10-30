<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriqueCompteSpecial extends Model
{
    use HasFactory;

    protected $table = 'historique_compte_special';

    protected $fillable = [
        'client_nom',
        'cycle_id',
        'montant',
        'devise',
        'description',
    ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // si client_nom déjà présent, on ne touche pas
            if (!empty($model->client_nom)) {
                return;
            }

            // essayer d'obtenir via la relation cycle si fournie
            if ($model->cycle_id) {
                $cycle = Cycle::with('client')->find($model->cycle_id);
                if ($cycle && $cycle->client) {
                    $client = $cycle->client;
                    $model->client_nom = trim("{$client->nom} {$client->postnom} {$client->prenom}");
                    return;
                }
            }

           
        });
    }
}
