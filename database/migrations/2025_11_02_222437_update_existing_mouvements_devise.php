<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Mouvement;
use App\Models\Caisse;
use App\Models\Compte;
use App\Models\CompteTransitoire;

return new class extends Migration
{
    public function up()
    {
        // Mettre à jour les mouvements existants avec la bonne devise
        $mouvements = Mouvement::where('devise', 'USD')->get();
        
        foreach ($mouvements as $mouvement) {
            $devise = 'USD'; // par défaut
            
            if ($mouvement->caisse_id) {
                $caisse = Caisse::find($mouvement->caisse_id);
                if ($caisse) {
                    $devise = $caisse->devise;
                }
            } elseif ($mouvement->compte_id) {
                $compte = Compte::find($mouvement->compte_id);
                if ($compte) {
                    $devise = $compte->devise;
                }
            }
            
            if ($devise !== 'USD') {
                $mouvement->update(['devise' => $devise]);
            }
        }
    }

    public function down()
    {
        // Ne rien faire en rollback
    }
};