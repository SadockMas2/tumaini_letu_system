<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            // CORRIGER LES MOUVEMENTS D'OCTROI DE CRÉDIT GROUPE
            DB::table('mouvements')
                ->where('type_mouvement', 'paiement_credit')
                ->where('description', 'LIKE', '%Crédit groupe reçu%')
                ->orWhere('description', 'LIKE', '%Crédit groupe reçu%')
                ->orWhere('description', 'LIKE', '%credit_groupe_recu%')
                ->orWhere('reference', 'LIKE', '%CREDIT-GRP-%')
                ->update([
                    'type_mouvement' => 'credit_octroye',
                    'updated_at' => now()
                ]);

            // CORRIGER LES MOUVEMENTS D'OCTROI DE CRÉDIT INDIVIDUEL
            DB::table('mouvements')
                ->where('type_mouvement', 'paiement_credit')
                ->where(function($query) {
                    $query->where('description', 'LIKE', '%Crédit accordé%')
                          ->orWhere('description', 'LIKE', '%octroi crédit%')
                          ->orWhere('description', 'LIKE', '%crédit individuel%')
                          ->orWhere('reference', 'LIKE', '%CREDIT-IND-%');
                })
                ->update([
                    'type_mouvement' => 'credit_octroye',
                    'updated_at' => now()
                ]);

            // CORRIGER LA CAUTION BLOQUÉE
            DB::table('mouvements')
                ->where('type_mouvement', 'paiement_credit')
                ->where('description', 'LIKE', '%Caution bloquée%')
                ->orWhere('description', 'LIKE', '%caution_bloquee%')
                ->orWhere('reference', 'LIKE', '%CAUTION-%')
                ->update([
                    'type_mouvement' => 'caution_bloquee',
                    'updated_at' => now()
                ]);

            // VÉRIFICATION
            $octroyeCount = DB::table('mouvements')
                ->where('type_mouvement', 'credit_octroye')
                ->count();
            
            $cautionCount = DB::table('mouvements')
                ->where('type_mouvement', 'caution_bloquee')
                ->count();
            
            Log::info("Migrations terminées: {$octroyeCount} mouvements 'credit_octroye', {$cautionCount} mouvements 'caution_bloquee'");
        });
    }

    public function down()
    {
        // Rollback
        DB::table('mouvements')
            ->where('type_mouvement', 'credit_octroye')
            ->update([
                'type_mouvement' => 'paiement_credit',
                'updated_at' => now()
            ]);

        DB::table('mouvements')
            ->where('type_mouvement', 'caution_bloquee')
            ->update([
                'type_mouvement' => 'paiement_credit',
                'updated_at' => now()
            ]);
    }
};