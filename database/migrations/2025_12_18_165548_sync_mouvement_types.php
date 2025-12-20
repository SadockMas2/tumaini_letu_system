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
        // Synchroniser les type_mouvement basés sur le type et la description
        DB::transaction(function () {
            // 1. Dépôts génériques
            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->where('type', 'depot')
                ->where(function($query) {
                    $query->where('description', 'like', '%remboursement%')
                          ->orWhere('description', 'like', '%DEPOT%')
                          ->orWhere('description', 'like', '%depot%')
                          ->orWhere('description', 'like', '%caution%');
                })
                ->update([
                    'type_mouvement' => 'depot_compte',
                    'updated_at' => now()
                ]);

            // 2. Retraits génériques
            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->where('type', 'retrait')
                ->where(function($query) {
                    $query->where('description', 'like', '%RETRAIT%')
                          ->orWhere('description', 'like', '%retrait%')
                          ->orWhere('description', 'like', '%credit%');
                })
                ->update([
                    'type_mouvement' => 'retrait_compte',
                    'updated_at' => now()
                ]);

            // 3. Retraits de crédit spécifiques
            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->where('type', 'retrait')
                ->where(function($query) {
                    $query->where('description', 'like', '%CREDIT%')
                          ->orWhere('description', 'like', '%credit%');
                })
                ->update([
                    'type_mouvement' => 'paiement_credit',
                    'updated_at' => now()
                ]);

            // 4. Distribution depuis comptabilité
            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->where('type', 'depot')
                ->where(function($query) {
                    $query->where('description', 'like', '%distribution%')
                          ->orWhere('description', 'like', '%comptabilité%')
                          ->orWhere('description', 'like', '%APPROVISIO%');
                })
                ->update([
                    'type_mouvement' => 'distribution_comptabilite',
                    'updated_at' => now()
                ]);

            // 5. Dépôts de caution
            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->where('type', 'depot')
                ->where('description', 'like', '%caution%')
                ->update([
                    'type_mouvement' => 'depot_caution',
                    'updated_at' => now()
                ]);

            // 6. Pour les cas restants sans description spécifique
            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->where('type', 'depot')
                ->update([
                    'type_mouvement' => 'depot_compte',
                    'updated_at' => now()
                ]);

            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->where('type', 'retrait')
                ->update([
                    'type_mouvement' => 'retrait_compte',
                    'updated_at' => now()
                ]);

            // 7. Pour tout autre type (s'il y en a)
            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->whereNotIn('type', ['depot', 'retrait'])
                ->update([
                    'type_mouvement' => 'autre_operation',
                    'updated_at' => now()
                ]);
        });

        // Vérifier qu'il n'y a plus de NULL
        $nullCount = DB::table('mouvements')->whereNull('type_mouvement')->count();
        
        if ($nullCount === 0) {
            // Ajouter une contrainte pour s'assurer que type_mouvement n'est plus null
            DB::statement("ALTER TABLE mouvements MODIFY type_mouvement VARCHAR(255) NOT NULL AFTER type");
        } else {
            Log::warning("Il reste {$nullCount} mouvements avec type_mouvement NULL");
            
            // Mettre une valeur par défaut pour les derniers
            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->update([
                    'type_mouvement' => 'operation_inconnue',
                    'updated_at' => now()
                ]);
            
            // Puis ajouter la contrainte
            DB::statement("ALTER TABLE mouvements MODIFY type_mouvement VARCHAR(255) NOT NULL AFTER type");
        }
    }

    public function down()
    {
        // En cas de rollback
        Schema::table('mouvements', function (Blueprint $table) {
            $table->string('type_mouvement')->nullable()->change();
        });
    }
};