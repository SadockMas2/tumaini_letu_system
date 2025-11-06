<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            // 1. Supprimer d'abord la contrainte de clé étrangère
            $table->dropForeign(['caisse_id']);
            
            // 2. Maintenant on peut modifier la colonne pour la rendre nullable
            $table->unsignedBigInteger('caisse_id')->nullable()->change();
            
            // 3. Recréer la contrainte de clé étrangère avec onDelete('set null')
            $table->foreign('caisse_id')
                  ->references('id')
                  ->on('caisses')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            // 1. Supprimer la contrainte de clé étrangère
            $table->dropForeign(['caisse_id']);
            
            // 2. Remettre la colonne en non nullable
            $table->unsignedBigInteger('caisse_id')->nullable(false)->change();
            
            // 3. Recréer la contrainte de clé étrangère originale
            $table->foreign('caisse_id')
                  ->references('id')
                  ->on('caisses')
                  ->onDelete('cascade');
        });
    }
};