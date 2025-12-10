<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Supprimer la contrainte foreign key existante
        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->dropForeign(['credit_id']);
        });

        // Rendre la colonne nullable
        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->foreignId('credit_id')->nullable()->change();
        });

        // Recréer la contrainte foreign key mais nullable
        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->foreign('credit_id')->references('id')->on('credits')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->dropForeign(['credit_id']);
        });

        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->foreignId('credit_id')->nullable(false)->change();
        });

        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->foreign('credit_id')->references('id')->on('credits')->onDelete('cascade');
        });
    }
};