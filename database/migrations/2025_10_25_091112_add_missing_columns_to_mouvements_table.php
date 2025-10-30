<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            // Ajouter toutes les colonnes manquantes
            if (!Schema::hasColumn('mouvements', 'type_mouvement')) {
                $table->string('type_mouvement')->nullable()->after('type');
            }
            
            if (!Schema::hasColumn('mouvements', 'reference')) {
                $table->string('reference')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('mouvements', 'date_mouvement')) {
                $table->timestamp('date_mouvement')->nullable()->after('reference');
            }
            
            if (!Schema::hasColumn('mouvements', 'numero_compte')) {
                $table->string('numero_compte')->nullable()->after('compte_id');
            }
            
            if (!Schema::hasColumn('mouvements', 'client_nom')) {
                $table->string('client_nom')->nullable()->after('numero_compte');
            }
            
            if (!Schema::hasColumn('mouvements', 'nom_deposant')) {
                $table->string('nom_deposant')->nullable()->after('client_nom');
            }
            
            if (!Schema::hasColumn('mouvements', 'solde_apres')) {
                $table->decimal('solde_apres', 10, 2)->default(0)->after('montant');
            }
        });
    }

    public function down()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->dropColumn([
                'type_mouvement',
                'reference', 
                'date_mouvement',
                'numero_compte',
                'client_nom',
                'nom_deposant',
                'solde_apres'
            ]);
        });
    }
};