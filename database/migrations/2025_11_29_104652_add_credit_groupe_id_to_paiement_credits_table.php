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
        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->foreignId('credit_groupe_id')
                  ->nullable()
                  ->after('credit_id')
                  ->constrained('credit_groupes')
                  ->onDelete('cascade');
            
            // Ajouter un index pour améliorer les performances
            $table->index(['credit_groupe_id', 'date_paiement']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->dropForeign(['credit_groupe_id']);
            $table->dropColumn('credit_groupe_id');
        });
    }
};