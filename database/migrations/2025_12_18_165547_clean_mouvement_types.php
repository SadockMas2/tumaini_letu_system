<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Vérifier d'abord s'il y a des valeurs NULL
        $nullCount = DB::table('mouvements')->whereNull('type_mouvement')->count();
        
        if ($nullCount > 0) {
            // Mettre à jour tous les NULL avec des valeurs par défaut
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

            // Pour tout autre type
            DB::table('mouvements')
                ->whereNull('type_mouvement')
                ->whereNotIn('type', ['depot', 'retrait'])
                ->update([
                    'type_mouvement' => 'autre_operation',
                    'updated_at' => now()
                ]);
        }
    }

    public function down()
    {
        // Rollback n'est pas critique pour cette migration
    }
};